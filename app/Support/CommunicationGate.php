<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class CommunicationGate
{
    public function canBroadcastTargeted(User $sender): bool
    {
        return $sender->hasRole(UserRole::ADMIN->value);
    }

    public function canBroadcastAll(User $sender): bool
    {
        return $sender->hasAnyRole([UserRole::ADMIN->value, UserRole::HR->value]);
    }

    public function canBroadcastTeam(User $sender): bool
    {
        return $this->isSupervisor($sender);
    }

    public function canDirectMessage(User $sender, User $receiver): bool
    {
        if ($sender->id === $receiver->id) {
            return false;
        }

        return $this->directRecipientIds($sender)->contains($receiver->id);
    }

    public function isSupervisor(User $sender): bool
    {
        return $sender->isSupervisor();
    }

    /**
     * @return Collection<int, User>
     */
    public function directRecipients(User $sender): Collection
    {
        $recipientIds = $this->directRecipientIds($sender);
        if ($recipientIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $recipientIds->all())
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function teamRecipients(User $sender): Collection
    {
        if (! $this->isSupervisor($sender)) {
            return collect();
        }

        return $sender->directReportsQuery()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function allEmployeeRecipients(User $sender): Collection
    {
        return $this->employeeRecipientsQuery($sender)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function branchRecipients(User $sender, string $branch): Collection
    {
        $normalizedBranch = trim($branch);
        if ($normalizedBranch === '') {
            return collect();
        }

        return $this->employeeRecipientsQuery($sender)
            ->whereHas('profile', function (Builder $query) use ($normalizedBranch): void {
                $query->where('branch', $normalizedBranch);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function teamRecipientsByDepartment(User $sender, string $team): Collection
    {
        $normalizedTeam = trim($team);
        if ($normalizedTeam === '') {
            return collect();
        }

        return $this->employeeRecipientsQuery($sender)
            ->whereHas('profile', function (Builder $query) use ($normalizedTeam): void {
                $query->where('department', $normalizedTeam);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @param list<int> $employeeIds
     * @return Collection<int, User>
     */
    public function recipientsByEmployeeIds(User $sender, array $employeeIds): Collection
    {
        $normalizedIds = collect($employeeIds)
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($normalizedIds->isEmpty()) {
            return collect();
        }

        return $this->employeeRecipientsQuery($sender)
            ->whereIn('id', $normalizedIds->all())
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, int>
     */
    public function directRecipientIds(User $sender): Collection
    {
        if ($this->canBroadcastAll($sender)) {
            $query = $this->employeeRecipientsQuery($sender)
                ->whereKeyNot($sender->id);

            return $query->pluck('id');
        }

        if ($this->isSupervisor($sender)) {
            return $sender->directReportsQuery()->pluck('id');
        }

        $adminAndHrQuery = User::query()
            ->whereIn('role', [UserRole::ADMIN->value, UserRole::HR->value])
            ->whereKeyNot($sender->id);

        $adminAndHrIds = $this->scopeByCompany($adminAndHrQuery, $sender)->pluck('id');

        $supervisorId = (int) ($sender->profile?->supervisor_user_id ?? 0);
        if ($supervisorId > 0) {
            $adminAndHrIds->push($supervisorId);
        }

        return $adminAndHrIds
            ->unique()
            ->values();
    }

    private function employeeRecipientsQuery(User $sender): Builder
    {
        $query = User::query()
            ->where('role', UserRole::EMPLOYEE->value);

        return $this->scopeByCompany($query, $sender);
    }

    private function scopeByCompany(Builder $query, ?User $viewer): Builder
    {
        if (! $viewer instanceof User) {
            return $query;
        }

        if (! Schema::hasColumn('users', 'company_id')) {
            return $query;
        }

        $companyId = $viewer->getAttribute('company_id');
        if ($companyId === null) {
            return $query->whereNull('company_id');
        }

        return $query->where('company_id', $companyId);
    }
}
