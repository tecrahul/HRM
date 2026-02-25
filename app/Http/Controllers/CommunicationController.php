<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageDraft;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\CommunicationGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CommunicationController extends Controller
{
    public function index(Request $request, CommunicationGate $communicationGate): View
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        $tab = (string) $request->string('tab', 'inbox');
        // support: inbox, outbox(sent), drafts, bin
        if ($tab === 'outbox') {
            $tab = 'sent';
        }
        if (! in_array($tab, ['inbox', 'sent', 'drafts', 'bin'], true)) {
            $tab = 'inbox';
        }

        $status = (string) $request->string('status', 'all');
        if (! in_array($status, ['all', 'read', 'unread'], true)) {
            $status = 'all';
        }

        $inboxQuery = Message::query()
            ->with(['sender.profile', 'conversation'])
            ->where('receiver_id', $viewer->id)
            ->whereNull('receiver_trashed_at');

        $sentQuery = Message::query()
            ->with(['receiver.profile', 'conversation'])
            ->where('sender_id', $viewer->id)
            ->whereNull('sender_trashed_at');

        if ($status === 'read') {
            $inboxQuery->where('read_status', true);
            $sentQuery->where('read_status', true);
        } elseif ($status === 'unread') {
            $inboxQuery->where('read_status', false);
            $sentQuery->where('read_status', false);
        }

        $inboxMessages = $inboxQuery
            ->latest()
            ->paginate(12, ['*'], 'inbox_page')
            ->withQueryString();

        $sentMessages = $sentQuery
            ->latest()
            ->paginate(12, ['*'], 'sent_page')
            ->withQueryString();

        // Drafts owned by viewer
        $drafts = MessageDraft::query()
            ->with(['receiver'])
            ->where('user_id', $viewer->id)
            ->latest()
            ->paginate(12, ['*'], 'drafts_page')
            ->withQueryString();

        // Bin: messages trashed by viewer (as sender or receiver)
        $binQuery = Message::query()
            ->with(['sender.profile', 'receiver.profile', 'conversation'])
            ->where(function ($q) use ($viewer): void {
                $q->where(function ($q2) use ($viewer): void {
                    $q2->where('receiver_id', $viewer->id)->whereNotNull('receiver_trashed_at');
                })->orWhere(function ($q2) use ($viewer): void {
                    $q2->where('sender_id', $viewer->id)->whereNotNull('sender_trashed_at');
                });
            });

        $binMessages = $binQuery
            ->latest()
            ->paginate(12, ['*'], 'bin_page')
            ->withQueryString();

        $broadcastTargetEmployees = collect();
        $broadcastTargetBranchOptions = collect();
        $broadcastTargetTeamOptions = collect();
        if ($communicationGate->canBroadcastAll($viewer)) {
            $broadcastTargetEmployees = $communicationGate->allEmployeeRecipients($viewer)
                ->loadMissing('profile')
                ->values();

            $broadcastTargetBranchOptions = $broadcastTargetEmployees
                ->pluck('profile.branch')
                ->filter(static fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->map(static fn (string $branch): string => trim($branch))
                ->unique()
                ->sort()
                ->values();

            $broadcastTargetTeamOptions = $broadcastTargetEmployees
                ->pluck('profile.department')
                ->filter(static fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->map(static fn (string $team): string => trim($team))
                ->unique()
                ->sort()
                ->values();
        }

        $inboxTotalCount = (int) Message::query()->where('receiver_id', $viewer->id)->whereNull('receiver_trashed_at')->count();
        $inboxUnreadCount = (int) Message::query()
            ->where('receiver_id', $viewer->id)
            ->where('read_status', false)
            ->whereNull('receiver_trashed_at')
            ->count();
        $inboxReadCount = max($inboxTotalCount - $inboxUnreadCount, 0);

        $sentTotalCount = (int) Message::query()->where('sender_id', $viewer->id)->whereNull('sender_trashed_at')->count();
        $sentReadCount = (int) Message::query()
            ->where('sender_id', $viewer->id)
            ->where('read_status', true)
            ->whereNull('sender_trashed_at')
            ->count();
        $sentUnreadCount = max($sentTotalCount - $sentReadCount, 0);

        $draftsCount = (int) MessageDraft::query()->where('user_id', $viewer->id)->count();
        $binCount = (int) $binQuery->count();

        return view('modules.communication.index', [
            'tab' => $tab,
            'statusFilter' => $status,
            'inboxMessages' => $inboxMessages,
            'sentMessages' => $sentMessages,
            'drafts' => $drafts,
            'binMessages' => $binMessages,
            'directRecipients' => $communicationGate->directRecipients($viewer),
            'teamRecipients' => $communicationGate->teamRecipients($viewer),
            'canBroadcastAll' => $communicationGate->canBroadcastAll($viewer),
            'canBroadcastTeam' => $communicationGate->canBroadcastTeam($viewer),
            'broadcastTargetBranchOptions' => $broadcastTargetBranchOptions,
            'broadcastTargetTeamOptions' => $broadcastTargetTeamOptions,
            'broadcastTargetEmployees' => $broadcastTargetEmployees,
            'messageFilterCounts' => [
                'inbox' => [
                    'all' => $inboxTotalCount,
                    'read' => $inboxReadCount,
                    'unread' => $inboxUnreadCount,
                ],
                'sent' => [
                    'all' => $sentTotalCount,
                    'read' => $sentReadCount,
                    'unread' => $sentUnreadCount,
                ],
            ],
            'stats' => [
                'inbox' => $inboxTotalCount,
                'sent' => $sentTotalCount,
                'unread' => $inboxUnreadCount,
                'drafts' => $draftsCount,
                'bin' => $binCount,
            ],
        ]);
    }

    public function storeDraft(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        $validated = $request->validate([
            'receiver_id' => ['nullable', 'integer', 'exists:users,id'],
            'message' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:5120',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ]);

        $attachments = $this->storeAttachments($request);

        MessageDraft::query()->create([
            'user_id' => $viewer->id,
            'receiver_id' => isset($validated['receiver_id']) && (int) $validated['receiver_id'] > 0 ? (int) $validated['receiver_id'] : null,
            'message' => trim((string) ($validated['message'] ?? '')),
            'attachments' => $attachments === [] ? null : $attachments,
        ]);

        return redirect()->route('modules.communication.index', ['tab' => 'drafts'])
            ->with('status', 'Draft saved.');
    }

    public function destroyDraft(Request $request, MessageDraft $draft): RedirectResponse
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);
        if ((int) $draft->user_id !== (int) $viewer->id) {
            abort(404);
        }
        $draft->delete();
        return redirect()->back()->with('status', 'Draft deleted.');
    }

    public function sendDraft(Request $request, MessageDraft $draft): RedirectResponse
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);
        if ((int) $draft->user_id !== (int) $viewer->id) {
            abort(404);
        }
        $receiverId = (int) ($draft->receiver_id ?? 0);
        abort_if($receiverId <= 0, 422, 'Draft must have a recipient before sending.');

        $receiver = User::query()->findOrFail($receiverId);

        // Reuse direct message flow
        $conversation = $this->resolveDirectConversation($viewer, $receiver);
        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $viewer->id,
            'receiver_id' => $receiver->id,
            'message' => trim((string) ($draft->message ?? '')),
            'attachments' => is_array($draft->attachments) && $draft->attachments !== [] ? $draft->attachments : null,
            'is_broadcast' => false,
            'read_status' => false,
        ]);
        $conversation->forceFill(['last_message_at' => now()])->save();

        $draft->delete();

        return redirect()->route('modules.communication.index', ['tab' => 'sent'])
            ->with('status', 'Draft sent.');
    }

    public function trashMessage(Request $request, Message $message): RedirectResponse
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        if ((int) $message->receiver_id === (int) $viewer->id) {
            if ($message->receiver_trashed_at === null) {
                $message->forceFill(['receiver_trashed_at' => now()])->save();
            }
        } elseif ((int) $message->sender_id === (int) $viewer->id) {
            if ($message->sender_trashed_at === null) {
                $message->forceFill(['sender_trashed_at' => now()])->save();
            }
        } else {
            abort(404);
        }

        return redirect()->back()->with('status', 'Moved to Bin.');
    }

    public function restoreMessage(Request $request, Message $message): RedirectResponse
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        if ((int) $message->receiver_id === (int) $viewer->id) {
            if ($message->receiver_trashed_at !== null) {
                $message->forceFill(['receiver_trashed_at' => null])->save();
            }
        } elseif ((int) $message->sender_id === (int) $viewer->id) {
            if ($message->sender_trashed_at !== null) {
                $message->forceFill(['sender_trashed_at' => null])->save();
            }
        } else {
            abort(404);
        }

        return redirect()->back()->with('status', 'Message restored.');
    }

    public function sendDirectMessage(
        Request $request,
        CommunicationGate $communicationGate
    ): RedirectResponse {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        $validated = $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:5120',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ]);

        $receiver = User::query()->findOrFail((int) $validated['receiver_id']);
        if (! $communicationGate->canDirectMessage($viewer, $receiver)) {
            abort(403, 'You are not allowed to message this user.');
        }

        $attachments = $this->storeAttachments($request);

        DB::transaction(function () use ($viewer, $receiver, $validated, $attachments): void {
            $conversation = $this->resolveDirectConversation($viewer, $receiver);
            Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $viewer->id,
                'receiver_id' => $receiver->id,
                'message' => trim((string) $validated['message']),
                'attachments' => $attachments === [] ? null : $attachments,
                'is_broadcast' => false,
                'read_status' => false,
            ]);

            $conversation->forceFill([
                'last_message_at' => now(),
            ])->save();
        });

        ActivityLogger::log(
            $viewer,
            'communication.direct.sent',
            'Direct message sent',
            "{$viewer->name} -> {$receiver->name}",
            '#3b82f6',
            $receiver
        );

        return redirect()
            ->route('modules.communication.index', ['tab' => 'sent'])
            ->with('status', 'Message sent.');
    }

    public function sendBroadcastAll(
        Request $request,
        CommunicationGate $communicationGate
    ): RedirectResponse {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        if (! $communicationGate->canBroadcastAll($viewer)) {
            abort(403, 'Only Admin/HR can broadcast to all employees.');
        }

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:5120',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ]);

        $recipients = $communicationGate->allEmployeeRecipients($viewer)
            ->where('id', '!=', $viewer->id)
            ->values();

        if ($recipients->isEmpty()) {
            return redirect()
                ->route('modules.communication.index')
                ->with('error', 'No employee recipients found for broadcast.');
        }

        $attachments = $this->storeAttachments($request);
        $messageBody = trim((string) $validated['message']);
        $subject = trim((string) ($validated['subject'] ?? '')) ?: 'Company Broadcast';

        DB::transaction(function () use ($viewer, $recipients, $attachments, $messageBody, $subject): void {
            $conversation = Conversation::query()->create([
                'type' => Conversation::TYPE_BROADCAST_ALL,
                'created_by_user_id' => $viewer->id,
                'subject' => $subject,
                'last_message_at' => now(),
            ]);

            $timestamp = now();
            $rows = [];
            foreach ($recipients as $recipient) {
                $rows[] = [
                    'conversation_id' => $conversation->id,
                    'sender_id' => $viewer->id,
                    'receiver_id' => $recipient->id,
                    'message' => $messageBody,
                    'attachments' => $attachments === [] ? null : json_encode($attachments, JSON_THROW_ON_ERROR),
                    'is_broadcast' => true,
                    'read_status' => false,
                    'read_at' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            Message::query()->insert($rows);
        });

        ActivityLogger::log(
            $viewer,
            'communication.broadcast.all.sent',
            'Broadcast sent to all employees',
            "{$recipients->count()} recipient(s)",
            '#7c3aed',
            $viewer
        );

        return redirect()
            ->route('modules.communication.index', ['tab' => 'sent'])
            ->with('status', 'Broadcast sent to all employees.');
    }

    public function sendBroadcast(
        Request $request,
        CommunicationGate $communicationGate
    ): RedirectResponse {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        if (! $communicationGate->canBroadcastAll($viewer)) {
            abort(403, 'Only Admin/HR can send broadcast messages.');
        }

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
            'target_branch' => ['nullable', 'string', 'max:120'],
            'target_team' => ['nullable', 'string', 'max:120'],
            'employee_ids' => ['nullable', 'array', 'max:500'],
            'employee_ids.*' => ['integer', Rule::exists('users', 'id')],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:5120',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ]);

        $allRecipients = $communicationGate->allEmployeeRecipients($viewer)
            ->loadMissing('profile')
            ->values();

        if ($allRecipients->isEmpty()) {
            return redirect()
                ->route('modules.communication.index')
                ->with('error', 'No employee recipients found for broadcast.');
        }

        $targetBranchRaw = trim((string) ($validated['target_branch'] ?? 'all'));
        $targetTeamRaw = trim((string) ($validated['target_team'] ?? 'all'));

        $isAllBranch = $targetBranchRaw === '' || strcasecmp($targetBranchRaw, 'all') === 0;
        $isAllTeam = $targetTeamRaw === '' || strcasecmp($targetTeamRaw, 'all') === 0;

        $branchFilteredRecipients = $allRecipients;
        if (! $isAllBranch) {
            $availableBranches = $allRecipients
                ->pluck('profile.branch')
                ->filter(static fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->map(static fn (string $branch): string => trim($branch))
                ->unique()
                ->values();

            if (! $availableBranches->contains($targetBranchRaw)) {
                return redirect()
                    ->route('modules.communication.index')
                    ->with('error', 'Selected branch is invalid.');
            }

            $branchFilteredRecipients = $allRecipients
                ->filter(static fn (User $recipient): bool => trim((string) ($recipient->profile?->branch ?? '')) === $targetBranchRaw)
                ->values();
        }

        $scopeRecipients = $branchFilteredRecipients;
        if (! $isAllTeam) {
            $availableTeams = $branchFilteredRecipients
                ->pluck('profile.department')
                ->filter(static fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->map(static fn (string $team): string => trim($team))
                ->unique()
                ->values();

            if (! $availableTeams->contains($targetTeamRaw)) {
                return redirect()
                    ->route('modules.communication.index')
                    ->with('error', 'Selected team is invalid for the selected branch.');
            }

            $scopeRecipients = $branchFilteredRecipients
                ->filter(static fn (User $recipient): bool => trim((string) ($recipient->profile?->department ?? '')) === $targetTeamRaw)
                ->values();
        }

        if ($scopeRecipients->isEmpty()) {
            return redirect()
                ->route('modules.communication.index')
                ->with('error', 'No employee recipients found for selected branch/team filters.');
        }

        $employeeIds = collect((array) ($validated['employee_ids'] ?? []))
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $finalRecipients = $scopeRecipients;
        if ($employeeIds->isNotEmpty()) {
            $finalRecipients = $scopeRecipients
                ->whereIn('id', $employeeIds->all())
                ->values();

            if ($finalRecipients->count() !== $employeeIds->count()) {
                return redirect()
                    ->route('modules.communication.index')
                    ->with('error', 'One or more selected employees are invalid for current filters.');
            }
        }

        if ($finalRecipients->isEmpty()) {
            return redirect()
                ->route('modules.communication.index')
                ->with('error', 'Select at least one target employee.');
        }

        $attachments = $this->storeAttachments($request);
        $messageBody = trim((string) $validated['message']);
        $subject = trim((string) ($validated['subject'] ?? ''));
        $defaultSubject = ($isAllBranch && $isAllTeam && $employeeIds->isEmpty())
            ? 'Company Broadcast'
            : 'Targeted Broadcast';
        $resolvedSubject = $subject !== '' ? $subject : $defaultSubject;
        $conversationType = ($isAllBranch && $isAllTeam && $employeeIds->isEmpty())
            ? Conversation::TYPE_BROADCAST_ALL
            : Conversation::TYPE_BROADCAST_TARGETED;

        DB::transaction(function () use (
            $viewer,
            $finalRecipients,
            $attachments,
            $messageBody,
            $resolvedSubject,
            $conversationType
        ): void {
            $conversation = Conversation::query()->create([
                'type' => $conversationType,
                'created_by_user_id' => $viewer->id,
                'subject' => $resolvedSubject,
                'last_message_at' => now(),
            ]);

            $timestamp = now();
            $rows = [];
            foreach ($finalRecipients as $recipient) {
                $rows[] = [
                    'conversation_id' => $conversation->id,
                    'sender_id' => $viewer->id,
                    'receiver_id' => $recipient->id,
                    'message' => $messageBody,
                    'attachments' => $attachments === [] ? null : json_encode($attachments, JSON_THROW_ON_ERROR),
                    'is_broadcast' => true,
                    'read_status' => false,
                    'read_at' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            Message::query()->insert($rows);
        });

        $meta = [];
        if (! $isAllBranch) {
            $meta[] = "branch: {$targetBranchRaw}";
        }
        if (! $isAllTeam) {
            $meta[] = "team: {$targetTeamRaw}";
        }
        if ($employeeIds->isNotEmpty()) {
            $meta[] = 'employees: '.$employeeIds->count();
        }

        ActivityLogger::log(
            $viewer,
            'communication.broadcast.unified.sent',
            'Unified broadcast sent',
            "{$finalRecipients->count()} recipient(s)".($meta === [] ? '' : ' ('.implode(', ', $meta).')'),
            '#7c3aed',
            $viewer
        );

        return redirect()
            ->route('modules.communication.index', ['tab' => 'sent'])
            ->with('status', 'Broadcast sent successfully.');
    }

    public function sendBroadcastTeam(
        Request $request,
        CommunicationGate $communicationGate
    ): RedirectResponse {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        if (! $communicationGate->canBroadcastTeam($viewer)) {
            abort(403, 'Only supervisors can send team broadcasts.');
        }

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:5120',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ]);

        $recipients = $communicationGate->teamRecipients($viewer)->values();
        if ($recipients->isEmpty()) {
            return redirect()
                ->route('modules.communication.index')
                ->with('error', 'No team members found for team broadcast.');
        }

        $attachments = $this->storeAttachments($request);
        $messageBody = trim((string) $validated['message']);
        $subject = trim((string) ($validated['subject'] ?? '')) ?: 'Team Broadcast';

        DB::transaction(function () use ($viewer, $recipients, $attachments, $messageBody, $subject): void {
            $conversation = Conversation::query()->create([
                'type' => Conversation::TYPE_BROADCAST_TEAM,
                'created_by_user_id' => $viewer->id,
                'subject' => $subject,
                'last_message_at' => now(),
            ]);

            $timestamp = now();
            $rows = [];
            foreach ($recipients as $recipient) {
                $rows[] = [
                    'conversation_id' => $conversation->id,
                    'sender_id' => $viewer->id,
                    'receiver_id' => $recipient->id,
                    'message' => $messageBody,
                    'attachments' => $attachments === [] ? null : json_encode($attachments, JSON_THROW_ON_ERROR),
                    'is_broadcast' => true,
                    'read_status' => false,
                    'read_at' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            Message::query()->insert($rows);
        });

        ActivityLogger::log(
            $viewer,
            'communication.broadcast.team.sent',
            'Team broadcast sent',
            "{$recipients->count()} recipient(s)",
            '#0ea5e9',
            $viewer
        );

        return redirect()
            ->route('modules.communication.index', ['tab' => 'sent'])
            ->with('status', 'Team broadcast sent.');
    }

    public function sendTargetedBroadcast(
        Request $request,
        CommunicationGate $communicationGate
    ): RedirectResponse {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        if (! $communicationGate->canBroadcastTargeted($viewer)) {
            abort(403, 'Only admins can send targeted broadcasts.');
        }

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
            'target_branch' => ['nullable', 'string', 'max:120'],
            'target_team' => ['nullable', 'string', 'max:120'],
            'employee_ids' => ['nullable', 'array', 'max:500'],
            'employee_ids.*' => ['integer', Rule::exists('users', 'id')],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:5120',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ]);

        $targetBranch = trim((string) ($validated['target_branch'] ?? ''));
        $targetTeam = trim((string) ($validated['target_team'] ?? ''));
        $employeeIds = collect((array) ($validated['employee_ids'] ?? []))
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($targetBranch === '' && $targetTeam === '' && $employeeIds->isEmpty()) {
            return redirect()
                ->route('modules.communication.index')
                ->with('error', 'Select at least one target: branch, team, or employees.');
        }

        $recipients = collect();
        if ($targetBranch !== '') {
            $recipients = $recipients->merge($communicationGate->branchRecipients($viewer, $targetBranch));
        }

        if ($targetTeam !== '') {
            $recipients = $recipients->merge($communicationGate->teamRecipientsByDepartment($viewer, $targetTeam));
        }

        if ($employeeIds->isNotEmpty()) {
            $selectedRecipients = $communicationGate->recipientsByEmployeeIds($viewer, $employeeIds->all());
            if ($selectedRecipients->count() !== $employeeIds->count()) {
                return redirect()
                    ->route('modules.communication.index')
                    ->with('error', 'One or more selected employees are invalid for this company scope.');
            }

            $recipients = $recipients->merge($selectedRecipients);
        }

        $recipients = $recipients
            ->unique('id')
            ->sortBy('name')
            ->values();

        if ($recipients->isEmpty()) {
            return redirect()
                ->route('modules.communication.index')
                ->with('error', 'No employee recipients found for the selected targeting criteria.');
        }

        $attachments = $this->storeAttachments($request);
        $messageBody = trim((string) $validated['message']);
        $subject = trim((string) ($validated['subject'] ?? '')) ?: 'Targeted Broadcast';

        DB::transaction(function () use ($viewer, $recipients, $attachments, $messageBody, $subject): void {
            $conversation = Conversation::query()->create([
                'type' => Conversation::TYPE_BROADCAST_TARGETED,
                'created_by_user_id' => $viewer->id,
                'subject' => $subject,
                'last_message_at' => now(),
            ]);

            $timestamp = now();
            $rows = [];
            foreach ($recipients as $recipient) {
                $rows[] = [
                    'conversation_id' => $conversation->id,
                    'sender_id' => $viewer->id,
                    'receiver_id' => $recipient->id,
                    'message' => $messageBody,
                    'attachments' => $attachments === [] ? null : json_encode($attachments, JSON_THROW_ON_ERROR),
                    'is_broadcast' => true,
                    'read_status' => false,
                    'read_at' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            Message::query()->insert($rows);
        });

        $meta = [];
        if ($targetBranch !== '') {
            $meta[] = "branch: {$targetBranch}";
        }
        if ($targetTeam !== '') {
            $meta[] = "team: {$targetTeam}";
        }
        if ($employeeIds->isNotEmpty()) {
            $meta[] = 'employees: '.$employeeIds->count();
        }

        ActivityLogger::log(
            $viewer,
            'communication.broadcast.targeted.sent',
            'Targeted broadcast sent',
            "{$recipients->count()} recipient(s)".($meta === [] ? '' : ' ('.implode(', ', $meta).')'),
            '#8b5cf6',
            $viewer
        );

        return redirect()
            ->route('modules.communication.index', ['tab' => 'sent'])
            ->with('status', 'Targeted broadcast sent.');
    }

    public function markRead(Request $request, Message $message): RedirectResponse
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        if ((int) $message->receiver_id !== (int) $viewer->id) {
            abort(404);
        }

        if (! $message->read_status) {
            $message->forceFill([
                'read_status' => true,
                'read_at' => now(),
            ])->save();
        }

        return redirect()->back();
    }

    /**
     * @return list<string>
     */
    private function storeAttachments(Request $request): array
    {
        $files = $request->file('attachments');
        if (! is_array($files) || $files === []) {
            return [];
        }

        $paths = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $paths[] = 'storage/'.$file->store('communication-attachments', 'public');
        }

        return $paths;
    }

    private function resolveDirectConversation(User $sender, User $receiver): Conversation
    {
        $lowId = min((int) $sender->id, (int) $receiver->id);
        $highId = max((int) $sender->id, (int) $receiver->id);

        return Conversation::query()->firstOrCreate(
            [
                'direct_user_low_id' => $lowId,
                'direct_user_high_id' => $highId,
            ],
            [
                'type' => Conversation::TYPE_DIRECT,
                'created_by_user_id' => $sender->id,
                'last_message_at' => now(),
            ]
        );
    }

    /**
     * Permanently delete a single message from Bin for the current user.
     */
    public function destroyMessageNow(Request $request, Message $message): RedirectResponse
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        $ownsAsReceiver = (int) $message->receiver_id === (int) $viewer->id;
        $ownsAsSender = (int) $message->sender_id === (int) $viewer->id;
        if (! $ownsAsReceiver && ! $ownsAsSender) {
            abort(404);
        }

        // Only allow hard delete if the message is in this user's Bin
        $isInUserBin = ($ownsAsReceiver && $message->receiver_trashed_at !== null)
            || ($ownsAsSender && $message->sender_trashed_at !== null);
        abort_unless($isInUserBin, 403);

        $this->deleteMessageWithAttachments($message);

        return redirect()->back()->with('status', 'Message deleted permanently.');
    }

    /**
     * Permanently delete all messages in the current user's Bin.
     */
    public function destroyAllInBin(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        $binMessages = Message::query()
            ->where(function ($q) use ($viewer): void {
                $q->where(function ($q2) use ($viewer): void {
                    $q2->where('receiver_id', $viewer->id)->whereNotNull('receiver_trashed_at');
                })->orWhere(function ($q2) use ($viewer): void {
                    $q2->where('sender_id', $viewer->id)->whereNotNull('sender_trashed_at');
                });
            })
            ->get();

        foreach ($binMessages as $msg) {
            $this->deleteMessageWithAttachments($msg);
        }

        return redirect()->route('modules.communication.index', ['tab' => 'bin'])
            ->with('status', 'Bin emptied.');
    }

    /**
     * Best-effort attachment cleanup, then delete the message.
     */
    private function deleteMessageWithAttachments(Message $message): void
    {
        try {
            $attachments = (array) ($message->attachments ?? []);
            foreach ($attachments as $path) {
                $pathStr = (string) $path;
                if (str_starts_with($pathStr, 'storage/')) {
                    $diskPath = substr($pathStr, strlen('storage/'));
                    try {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($diskPath);
                    } catch (\Throwable) {
                        // ignore
                    }
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        $message->delete();
    }
}
