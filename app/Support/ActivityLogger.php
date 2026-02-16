<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ActivityLogger
{
    private static ?bool $activitiesTableExists = null;

    /**
     * @param array<string, mixed> $payload
     */
    public static function log(
        ?User $actor,
        string $eventKey,
        string $title,
        ?string $meta = null,
        ?string $tone = null,
        Model|int|null $subject = null,
        array $payload = []
    ): void {
        if (! self::hasActivitiesTable()) {
            return;
        }

        $subjectType = null;
        $subjectId = null;

        if ($subject instanceof Model) {
            $subjectType = $subject::class;
            $subjectId = $subject->getKey();
        } elseif (is_int($subject)) {
            $subjectId = $subject;
        }

        try {
            Activity::query()->create([
                'actor_user_id' => $actor?->id,
                'event_key' => Str::limit($eventKey, 100, ''),
                'title' => Str::limit($title, 180, ''),
                'meta' => $meta !== null ? Str::limit($meta, 255, '') : null,
                'tone' => $tone ?? self::defaultTone($eventKey),
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'payload' => $payload === [] ? null : $payload,
                'occurred_at' => now(),
            ]);
        } catch (Throwable) {
            // Never block business flows for activity write failures.
        }
    }

    private static function defaultTone(string $eventKey): string
    {
        return match (true) {
            str_contains($eventKey, 'attendance') => '#0ea5e9',
            str_contains($eventKey, 'leave') => '#f59e0b',
            str_contains($eventKey, 'payroll') => '#10b981',
            str_contains($eventKey, 'branch'),
            str_contains($eventKey, 'department') => '#db2777',
            str_contains($eventKey, 'profile') => '#ec4899',
            default => '#7c3aed',
        };
    }

    private static function hasActivitiesTable(): bool
    {
        if (self::$activitiesTableExists !== null) {
            return self::$activitiesTableExists;
        }

        try {
            self::$activitiesTableExists = Schema::hasTable('activities');
        } catch (Throwable) {
            self::$activitiesTableExists = false;
        }

        return self::$activitiesTableExists;
    }
}
