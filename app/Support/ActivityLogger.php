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
    private const MAX_PAYLOAD_DEPTH = 6;

    private const MAX_ARRAY_ITEMS = 100;

    private const MAX_STRING_LENGTH = 500;

    private const REDACTED_VALUE = '[REDACTED]';

    /**
     * @var list<string>
     */
    private const SENSITIVE_KEY_MARKERS = [
        'password',
        'passcode',
        'secret',
        'token',
        'authorization',
        'cookie',
        'session',
        'otp',
        'pin',
        'ssn',
        'pan',
        'bank_account',
        'account_number',
        'access_key',
        'refresh_key',
    ];

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
                'payload' => self::sanitizePayloadForStorage($payload),
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

    /**
     * @return array<string, mixed>
     */
    public static function sanitizePayloadForDisplay(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $sanitized = self::sanitizePayloadValue($payload, 0, null);
        if (! is_array($sanitized)) {
            return [];
        }

        return $sanitized;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    private static function sanitizePayloadForStorage(array $payload): ?array
    {
        $sanitized = self::sanitizePayloadForDisplay($payload);

        return $sanitized === [] ? null : $sanitized;
    }

    private static function sanitizePayloadValue(mixed $value, int $depth, ?string $key): mixed
    {
        if ($key !== null && self::shouldRedactKey($key)) {
            return self::REDACTED_VALUE;
        }

        if ($depth >= self::MAX_PAYLOAD_DEPTH) {
            return '[TRUNCATED_DEPTH]';
        }

        if (is_array($value)) {
            $sanitized = [];
            $count = 0;
            $total = count($value);

            foreach ($value as $itemKey => $itemValue) {
                if ($count >= self::MAX_ARRAY_ITEMS) {
                    $remaining = max(0, $total - self::MAX_ARRAY_ITEMS);
                    $sanitized['__truncated__'] = sprintf('%d additional entries removed', $remaining);
                    break;
                }

                $childKey = is_int($itemKey) ? (string) $itemKey : (string) $itemKey;
                $sanitized[$itemKey] = self::sanitizePayloadValue($itemValue, $depth + 1, $childKey);
                $count++;
            }

            return $sanitized;
        }

        if (is_string($value)) {
            return Str::limit($value, self::MAX_STRING_LENGTH, '...');
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return Str::limit((string) $value, self::MAX_STRING_LENGTH, '...');
            }

            return '[OBJECT '.class_basename($value).']';
        }

        if (is_resource($value)) {
            return '[RESOURCE]';
        }

        return $value;
    }

    private static function shouldRedactKey(string $key): bool
    {
        $normalized = strtolower($key);
        $normalized = str_replace([' ', '.', '-', ':'], '_', $normalized);

        foreach (self::SENSITIVE_KEY_MARKERS as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
    }
}
