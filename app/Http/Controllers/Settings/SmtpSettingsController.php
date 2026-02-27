<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Mail\SmtpTestMail;
use App\Models\AuditLog;
use App\Models\SmtpSetting;
use App\Models\User;
use App\Services\MailConfigurationManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class SmtpSettingsController extends Controller
{
    public function index(Request $request, MailConfigurationManager $mailConfigurationManager): View
    {
        $active = $mailConfigurationManager->activeConfiguration();
        $systemConfig = $mailConfigurationManager->systemConfiguration();
        $record = $active['record'];
        $latestRecord = $record ?? SmtpSetting::query()->with('updatedBy:id,name')->latest('updated_at')->latest('id')->first();
        $viewer = $request->user();

        $payload = [
            'mode' => $active['source'],
            'systemConfig' => $this->presentConfig($systemConfig),
            'customConfig' => $latestRecord ? $this->presentConfig(array_merge($latestRecord->toArray(), [
                'configured_by' => (string) ($latestRecord->updatedBy?->full_name ?? ''),
            ]), true) : null,
            'routes' => [
                'saveCustom' => route('settings.smtp.custom'),
                'useSystem' => route('settings.smtp.system'),
                'testEmail' => route('settings.smtp.test'),
            ],
            'permissions' => [
                'canManage' => $this->viewerCanManage($viewer),
                'canSendTest' => $this->viewerCanManage($viewer),
                'canSwitchMode' => $this->viewerCanManage($viewer),
            ],
            'messages' => [
                'systemInfo' => 'System mode uses .env configuration',
                'customWarning' => 'Switching to custom mode overrides .env values. Continue only if you trust the provided SMTP credentials.',
            ],
            'csrfToken' => csrf_token(),
        ];

        return view('settings.smtp', [
            'pagePayload' => $payload,
        ]);
    }

    public function activateSystem(Request $request, MailConfigurationManager $mailConfigurationManager): JsonResponse
    {
        $viewer = $request->user();
        $previous = SmtpSetting::query()->where('is_active', true)->get();

        DB::transaction(static function () use ($previous): void {
            if ($previous->isNotEmpty()) {
                SmtpSetting::query()->update(['is_active' => false]);
            }
        });

        $mailConfigurationManager->forgetCachedConfiguration();
        $mailConfigurationManager->applyRuntimeConfiguration();

        if ($viewer) {
            AuditLog::query()->create([
                'entity_type' => 'smtp_settings',
                'entity_id' => null,
                'action' => 'smtp_settings.system_mode_enabled',
                'performed_by_user_id' => $viewer->id,
                'old_values' => $this->maskForAudit($previous->first()?->toArray()),
                'new_values' => ['mode' => 'system'],
                'metadata' => ['changed' => $previous->isNotEmpty()],
                'performed_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'System environment configuration activated.',
            'mode' => 'system',
            'config' => $this->presentConfig($mailConfigurationManager->systemConfiguration()),
        ]);
    }

    public function storeCustom(Request $request, MailConfigurationManager $mailConfigurationManager): JsonResponse
    {
        $validated = $this->validateCustomPayload($request);
        $viewer = $request->user();
        $previous = SmtpSetting::query()->where('is_active', true)->first();

        $password = $validated['mail_password'] ?? null;
        if ($password === null && $previous !== null) {
            $password = $previous->mail_password;
        }

        $record = DB::transaction(static function () use ($validated, $viewer, $password): SmtpSetting {
            SmtpSetting::query()->update(['is_active' => false]);

            return SmtpSetting::query()->create([
                'mail_driver' => $validated['mail_driver'],
                'mail_host' => $validated['mail_host'],
                'mail_port' => $validated['mail_port'],
                'mail_username' => $validated['mail_username'] ?? null,
                'mail_password' => $password,
                'mail_encryption' => $validated['mail_encryption'] ?? null,
                'from_address' => $validated['from_address'] ?? null,
                'from_name' => $validated['from_name'] ?? null,
                'is_active' => true,
                'created_by' => $viewer?->id,
                'updated_by' => $viewer?->id,
            ]);
        });

        $mailConfigurationManager->forgetCachedConfiguration();
        $mailConfigurationManager->applyRuntimeConfiguration();

        if ($viewer) {
            AuditLog::query()->create([
                'entity_type' => 'smtp_settings',
                'entity_id' => $record->id,
                'action' => 'smtp_settings.custom_mode_saved',
                'performed_by_user_id' => $viewer->id,
                'old_values' => $this->maskForAudit($previous?->toArray()),
                'new_values' => $this->maskForAudit($record->toArray()),
                'metadata' => ['mode' => 'custom'],
                'performed_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Custom SMTP settings saved and activated.',
            'mode' => 'custom',
            'config' => $this->presentConfig($record->toArray(), true),
        ]);
    }

    public function sendTestEmail(Request $request, MailConfigurationManager $mailConfigurationManager): JsonResponse
    {
        $data = $request->validate([
            'recipient' => ['required', 'email:rfc,dns', 'max:180'],
        ]);

        $recipient = $data['recipient'];
        $viewer = $request->user();
        $active = $mailConfigurationManager->activeConfiguration();
        $mailConfigurationManager->applyRuntimeConfiguration();

        try {
        Mail::to($recipient)->send(new SmtpTestMail($viewer?->full_name ?? 'System Admin'));
        } catch (Throwable $exception) {
            if ($viewer) {
                AuditLog::query()->create([
                    'entity_type' => 'smtp_settings',
                    'entity_id' => null,
                    'action' => 'smtp_settings.test_email_failed',
                    'performed_by_user_id' => $viewer->id,
                    'metadata' => [
                        'recipient' => $recipient,
                        'mode' => $active['source'],
                        'error' => $exception->getMessage(),
                    ],
                    'performed_at' => now(),
                ]);
            }

            throw ValidationException::withMessages([
                'recipient' => 'Unable to send test email. Please verify the SMTP configuration and try again.',
            ]);
        }

        if ($viewer) {
            AuditLog::query()->create([
                'entity_type' => 'smtp_settings',
                'entity_id' => null,
                'action' => 'smtp_settings.test_email_sent',
                'performed_by_user_id' => $viewer->id,
                'metadata' => [
                    'recipient' => $recipient,
                    'mode' => $active['source'],
                ],
                'performed_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Test email sent successfully.',
        ]);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function presentConfig(array $config, bool $isRecord = false): array
    {
        return [
            'mail_driver' => (string) ($config['mail_driver'] ?? ''),
            'mail_host' => (string) ($config['mail_host'] ?? ''),
            'mail_port' => (int) ($config['mail_port'] ?? 0),
            'mail_username' => (string) ($config['mail_username'] ?? ''),
            'mail_encryption' => (string) ($config['mail_encryption'] ?? ''),
            'from_address' => (string) ($config['from_address'] ?? ''),
            'from_name' => (string) ($config['from_name'] ?? ''),
            'has_password' => filled($config['mail_password'] ?? null),
            'updated_at' => $isRecord && isset($config['updated_at']) ? (string) $config['updated_at'] : null,
            'configured_by' => $isRecord && isset($config['configured_by']) ? (string) $config['configured_by'] : null,
        ];
    }

    /**
     * @param array<string, mixed>|null $config
     * @return array<string, mixed>|null
     */
    private function maskForAudit(?array $config): ?array
    {
        if ($config === null) {
            return null;
        }

        unset($config['mail_password']);

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCustomPayload(Request $request): array
    {
        return $request->validate([
            'mail_driver' => ['required', 'string', 'in:smtp,sendmail', 'max:40'],
            'mail_host' => ['required', 'string', 'max:120'],
            'mail_port' => ['required', 'integer', 'between:1,65535'],
            'mail_username' => ['nullable', 'string', 'max:120'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'string', 'max:40'],
            'from_address' => ['required', 'email', 'max:120'],
            'from_name' => ['nullable', 'string', 'max:120'],
        ]);
    }

    private function viewerCanManage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
        ]);
    }
}
