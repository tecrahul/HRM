<?php

namespace App\Services;

use App\Models\SmtpSetting;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MailConfigurationManager
{
    private const CACHE_KEY = 'smtp_settings.active';

    private ?bool $tableExists = null;

    public function __construct(private ?CacheRepository $cache = null)
    {
        $this->cache = $cache ?? Cache::store();
    }

    public function useSystemMode(): bool
    {
        return $this->activeRecord() === null;
    }

    /**
     * @return array{source:string,config:array<string,mixed>,record:?SmtpSetting}
     */
    public function activeConfiguration(): array
    {
        $record = $this->activeRecord();

        if ($record === null) {
            return [
                'source' => 'system',
                'config' => $this->systemConfiguration(),
                'record' => null,
            ];
        }

        return [
            'source' => 'custom',
            'config' => [
                'mail_driver' => $record->mail_driver,
                'mail_host' => $record->mail_host,
                'mail_port' => $record->mail_port,
                'mail_username' => $record->mail_username,
                'mail_password' => $record->mail_password,
                'mail_encryption' => $record->mail_encryption,
                'from_address' => $record->from_address,
                'from_name' => $record->from_name,
            ],
            'record' => $record,
        ];
    }

    public function systemConfiguration(): array
    {
        return [
            'mail_driver' => (string) config('mail.default', 'smtp'),
            'mail_host' => (string) config('mail.mailers.smtp.host'),
            'mail_port' => (int) config('mail.mailers.smtp.port', 587),
            'mail_username' => config('mail.mailers.smtp.username'),
            'mail_password' => config('mail.mailers.smtp.password'),
            'mail_encryption' => config('mail.mailers.smtp.encryption') ?: config('mail.mailers.smtp.scheme'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];
    }

    public function applyRuntimeConfiguration(): void
    {
        $record = $this->activeRecord();
        if ($record === null) {
            return;
        }

        Config::set('mail.default', $record->mail_driver ?: 'smtp');
        Config::set('mail.mailers.smtp.host', $record->mail_host);
        Config::set('mail.mailers.smtp.port', $record->mail_port);
        Config::set('mail.mailers.smtp.username', $record->mail_username);
        Config::set('mail.mailers.smtp.password', $record->mail_password);
        Config::set('mail.mailers.smtp.encryption', $record->mail_encryption);
        Config::set('mail.mailers.smtp.scheme', $record->mail_encryption);
        Config::set('mail.from.address', $record->from_address ?: config('mail.from.address'));
        Config::set('mail.from.name', $record->from_name ?: config('mail.from.name'));
    }

    public function forgetCachedConfiguration(): void
    {
        $this->cache?->forget(self::CACHE_KEY);
    }

    private function activeRecord(): ?SmtpSetting
    {
        if (! $this->tableIsAvailable()) {
            return null;
        }

        /** @var SmtpSetting|null $record */
        $record = $this->cache?->remember(self::CACHE_KEY, now()->addMinutes(5), static function (): ?SmtpSetting {
            return SmtpSetting::query()
                ->where('is_active', true)
                ->latest('updated_at')
                ->latest('id')
                ->first();
        });

        return $record;
    }

    private function tableIsAvailable(): bool
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }

        try {
            $this->tableExists = Schema::hasTable('smtp_settings');
        } catch (Throwable) {
            $this->tableExists = false;
        }

        return $this->tableExists;
    }
}
