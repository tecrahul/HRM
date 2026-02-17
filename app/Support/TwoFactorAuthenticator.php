<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class TwoFactorAuthenticator
{
    private const TOTP_PERIOD = 30;
    private const TOTP_DIGITS = 6;
    private const DEFAULT_WINDOW = 1;
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 32): string
    {
        $length = max(16, min($length, 64));
        $secret = '';

        for ($index = 0; $index < $length; $index++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, strlen(self::BASE32_ALPHABET) - 1)];
        }

        return $secret;
    }

    public function formatSecretForDisplay(string $secret): string
    {
        return trim(chunk_split(strtoupper($secret), 4, ' '));
    }

    public function provisioningUri(User $user, string $secret): string
    {
        $issuer = (string) config('app.name', 'HRM');
        $label = rawurlencode($issuer.':'.$user->email);
        $query = http_build_query([
            'secret' => strtoupper($secret),
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::TOTP_DIGITS,
            'period' => self::TOTP_PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$query}";
    }

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $count = max(4, min($count, 12));
        $codes = [];

        for ($index = 0; $index < $count; $index++) {
            $codes[] = sprintf(
                '%s-%s',
                Str::upper(Str::random(4)),
                Str::upper(Str::random(4))
            );
        }

        return $codes;
    }

    public function verifyCode(string $secret, string $code, int $window = self::DEFAULT_WINDOW): bool
    {
        $normalizedCode = $this->normalizeOtpCode($code);
        if (strlen($normalizedCode) !== self::TOTP_DIGITS) {
            return false;
        }

        $baseTimestamp = time();

        for ($offset = -$window; $offset <= $window; $offset++) {
            $codeAtTime = $this->generateCodeAt($secret, $baseTimestamp + ($offset * self::TOTP_PERIOD));
            if (hash_equals($codeAtTime, $normalizedCode)) {
                return true;
            }
        }

        return false;
    }

    public function currentCode(string $secret): string
    {
        return $this->generateCodeAt($secret, time());
    }

    private function generateCodeAt(string $secret, int $timestamp): string
    {
        $counter = intdiv($timestamp, self::TOTP_PERIOD);
        $counterBytes = pack('N*', 0, $counter);
        $secretKey = $this->decodeBase32($secret);

        $hmac = hash_hmac('sha1', $counterBytes, $secretKey, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $segment = substr($hmac, $offset, 4);
        $value = unpack('N', $segment);
        $binaryCode = ($value[1] ?? 0) & 0x7FFFFFFF;
        $otp = $binaryCode % (10 ** self::TOTP_DIGITS);

        return str_pad((string) $otp, self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }

    private function normalizeOtpCode(string $code): string
    {
        $normalized = preg_replace('/\D+/', '', $code);

        return is_string($normalized) ? $normalized : '';
    }

    private function decodeBase32(string $secret): string
    {
        $filtered = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $buffer = 0;
        $bufferBits = 0;
        $decoded = '';

        $alphabetMap = array_flip(str_split(self::BASE32_ALPHABET));
        foreach (str_split($filtered) as $character) {
            if (! isset($alphabetMap[$character])) {
                continue;
            }

            $buffer = ($buffer << 5) | $alphabetMap[$character];
            $bufferBits += 5;

            while ($bufferBits >= 8) {
                $bufferBits -= 8;
                $decoded .= chr(($buffer >> $bufferBits) & 0xFF);
            }
        }

        return $decoded;
    }
}
