<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\CompanySetting;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\TwoFactorAuthenticator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ProfileController extends Controller
{
    public function edit(Request $request, TwoFactorAuthenticator $twoFactorAuthenticator): View
    {
        $user = $request->user();
        $user?->loadMissing('profile');
        $twoFactorFeatureEnabled = CompanySetting::twoFactorEnabled();
        $lastPasswordChangedAt = null;
        if ($user && Schema::hasTable('activities')) {
            $lastPasswordActivity = Activity::query()
                ->where('actor_user_id', $user->id)
                ->where('event_key', 'profile.password_updated')
                ->latest('occurred_at')
                ->first(['occurred_at']);
            $lastPasswordChangedAt = $lastPasswordActivity?->occurred_at;
            if (is_string($lastPasswordChangedAt)) {
                $lastPasswordChangedAt = Carbon::parse($lastPasswordChangedAt);
            }
        }

        $twoFactorSetup = null;
        if ($twoFactorFeatureEnabled && $user && ! $user->hasTwoFactorEnabled()) {
            $setupSecret = (string) $request->session()->get('profile.two_factor.pending_secret', '');
            if ($setupSecret === '') {
                $setupSecret = $twoFactorAuthenticator->generateSecret();
                $request->session()->put('profile.two_factor.pending_secret', $setupSecret);
            }

            $twoFactorSetup = [
                'secret' => $setupSecret,
                'secret_formatted' => $twoFactorAuthenticator->formatSecretForDisplay($setupSecret),
                'otpauth_uri' => $twoFactorAuthenticator->provisioningUri($user, $setupSecret),
            ];
        }

        $freshRecoveryCodes = session('two_factor_recovery_codes');

        return view('profile.edit', [
            'twoFactorSetup' => $twoFactorSetup,
            'freshRecoveryCodes' => is_array($freshRecoveryCodes) ? $freshRecoveryCodes : [],
            'twoFactorFeatureEnabled' => $twoFactorFeatureEnabled,
            'lastPasswordChangedAt' => $lastPasswordChangedAt,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'file', 'max:4096', 'mimetypes:image/jpeg,image/png,image/webp'],
            'phone' => ['nullable', 'string', 'max:40'],
            'alternate_phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'prefer_not_to_say'])],
            'nationality' => ['nullable', 'string', 'max:80'],
            'national_id' => ['nullable', 'string', 'max:80'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
        ]);

        $user->loadMissing('profile');
        $existingAvatarUrl = $user->profile?->avatar_url;
        $newAvatarUrl = null;
        if ($request->hasFile('avatar')) {
            $newAvatarUrl = $this->sanitizeAndStoreAvatar($request->file('avatar'));
        }

        $user->update([
            'name' => $validated['name'],
        ]);

        $isEmployee = $user->profile?->is_employee ?? User::shouldTreatRoleAsEmployee(
            $user->role instanceof \App\Enums\UserRole ? $user->role : (string) $user->role
        );
        $employeeCode = $user->profile?->employee_code
            ?: ($isEmployee ? User::makeEmployeeCode($user->id) : null);

        $user->profile()->updateOrCreate([], [
            'is_employee' => $isEmployee,
            'employee_code' => $employeeCode,
            'avatar_url' => $newAvatarUrl ?: $existingAvatarUrl,
            'phone' => $validated['phone'] ?: null,
            'alternate_phone' => $validated['alternate_phone'] ?: null,
            'address' => $validated['address'] ?: null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?: null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?: null,
            'date_of_birth' => $validated['date_of_birth'] ?: null,
            'gender' => $validated['gender'] ?: null,
            'marital_status' => $validated['marital_status'] ?: null,
            'nationality' => $validated['nationality'] ?: null,
            'national_id' => $validated['national_id'] ?: null,
            'linkedin_url' => $validated['linkedin_url'] ?: null,
        ]);

        if ($newAvatarUrl && $existingAvatarUrl && $newAvatarUrl !== $existingAvatarUrl) {
            $this->deleteAvatarFile($existingAvatarUrl);
        }

        ActivityLogger::log(
            $user,
            'profile.updated',
            'Profile updated',
            "{$user->name} updated personal profile",
            '#ec4899',
            $user->profile
        );

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $validated = $request->validateWithBag('passwordUpdate', [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        ActivityLogger::log(
            $user,
            'profile.password_updated',
            'Password updated',
            "{$user->name} updated account password",
            '#7c3aed',
            $user
        );

        return redirect()
            ->route('profile.edit')
            ->with('password_status', 'Password changed successfully.');
    }

    public function enableTwoFactor(Request $request, TwoFactorAuthenticator $twoFactorAuthenticator): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if ($user->hasTwoFactorEnabled()) {
            return redirect()
                ->route('profile.edit')
                ->with('two_factor_status', 'Two-factor authentication is already enabled.')
                ->with('profile_scroll_target', 'profileMfaSection');
        }

        if (! CompanySetting::twoFactorEnabled()) {
            return redirect()
                ->route('profile.edit')
                ->with('two_factor_status', 'Two-factor authentication is disabled by admin settings.')
                ->with('profile_scroll_target', 'profileMfaSection');
        }

        $validated = $request->validateWithBag('twoFactorEnable', [
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        $pendingSecret = (string) $request->session()->get('profile.two_factor.pending_secret', '');
        if ($pendingSecret === '') {
            $request->session()->put('profile.two_factor.pending_secret', $twoFactorAuthenticator->generateSecret());
            $exception = ValidationException::withMessages([
                'code' => 'Two-factor setup expired. Try again with the refreshed secret.',
            ]);
            $exception->errorBag = 'twoFactorEnable';

            throw $exception;
        }

        if (! $twoFactorAuthenticator->verifyCode($pendingSecret, (string) $validated['code'])) {
            $exception = ValidationException::withMessages([
                'code' => 'Invalid authentication code. Please re-check your authenticator app.',
            ]);
            $exception->errorBag = 'twoFactorEnable';

            throw $exception;
        }

        $recoveryCodes = $twoFactorAuthenticator->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $pendingSecret,
            'two_factor_enabled_at' => now(),
        ]);
        $user->replaceTwoFactorRecoveryCodes($recoveryCodes);
        $user->save();

        $request->session()->forget('profile.two_factor.pending_secret');
        $request->session()->flash('two_factor_recovery_codes', $recoveryCodes);

        ActivityLogger::log(
            $user,
            'profile.two_factor.enabled',
            'Two-factor authentication enabled',
            "{$user->name} enabled two-factor authentication",
            '#10b981',
            $user
        );

        return redirect()
            ->route('profile.edit')
            ->with('two_factor_status', 'Two-factor authentication enabled successfully.')
            ->with('profile_scroll_target', 'profileMfaSection');
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()
                ->route('profile.edit')
                ->with('two_factor_status', 'Two-factor authentication is already disabled.')
                ->with('profile_scroll_target', 'profileMfaSection');
        }

        $request->validateWithBag('twoFactorDisable', [
            'current_password' => ['required', 'current_password'],
        ]);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled_at' => null,
        ])->save();

        $request->session()->forget('profile.two_factor.pending_secret');

        ActivityLogger::log(
            $user,
            'profile.two_factor.disabled',
            'Two-factor authentication disabled',
            "{$user->name} disabled two-factor authentication",
            '#6b7280',
            $user
        );

        return redirect()
            ->route('profile.edit')
            ->with('two_factor_status', 'Two-factor authentication disabled.')
            ->with('profile_scroll_target', 'profileMfaSection');
    }

    public function regenerateTwoFactorRecoveryCodes(
        Request $request,
        TwoFactorAuthenticator $twoFactorAuthenticator
    ): RedirectResponse {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()
                ->route('profile.edit')
                ->with('two_factor_status', 'Enable two-factor authentication first.')
                ->with('profile_scroll_target', 'profileMfaSection');
        }

        if (! CompanySetting::twoFactorEnabled()) {
            return redirect()
                ->route('profile.edit')
                ->with('two_factor_status', 'Two-factor authentication is disabled by admin settings.')
                ->with('profile_scroll_target', 'profileMfaSection');
        }

        $request->validateWithBag('twoFactorRecoveryCodes', [
            'current_password' => ['required', 'current_password'],
        ]);

        $recoveryCodes = $twoFactorAuthenticator->generateRecoveryCodes();
        $user->replaceTwoFactorRecoveryCodes($recoveryCodes);
        $user->save();
        $request->session()->flash('two_factor_recovery_codes', $recoveryCodes);

        ActivityLogger::log(
            $user,
            'profile.two_factor.recovery_codes_regenerated',
            'Two-factor recovery codes regenerated',
            "{$user->name} regenerated two-factor recovery codes",
            '#f59e0b',
            $user
        );

        return redirect()
            ->route('profile.edit')
            ->with('two_factor_status', 'Recovery codes regenerated. Store them in a safe place.')
            ->with('profile_scroll_target', 'profileMfaSection');
    }

    private function sanitizeAndStoreAvatar(UploadedFile $avatar): string
    {
        if (! $avatar->isValid()) {
            throw ValidationException::withMessages([
                'avatar' => 'Failed to upload image. Please try another file.',
            ]);
        }

        $sourcePath = $avatar->getRealPath();
        $imageInfo = $sourcePath ? @getimagesize($sourcePath) : false;
        if ($imageInfo === false) {
            throw ValidationException::withMessages([
                'avatar' => 'Uploaded file is not a valid image.',
            ]);
        }

        $mimeType = strtolower((string) ($imageInfo['mime'] ?? ''));
        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw ValidationException::withMessages([
                'avatar' => 'Only JPG, PNG, or WEBP images are allowed.',
            ]);
        }

        $requiredGdFunctions = [
            'imagecreatefromstring',
            'imagesx',
            'imagesy',
            'imagedestroy',
            'imagecreatetruecolor',
            'imagealphablending',
            'imagesavealpha',
            'imagecopyresampled',
            'imagepng',
        ];
        foreach ($requiredGdFunctions as $gdFunction) {
            if (! function_exists($gdFunction)) {
                throw ValidationException::withMessages([
                    'avatar' => 'Image processing is not available on this server. Please contact support.',
                ]);
            }
        }

        $imageContent = @file_get_contents((string) $sourcePath);
        $image = $imageContent !== false ? @\imagecreatefromstring($imageContent) : false;
        if ($image === false) {
            throw ValidationException::withMessages([
                'avatar' => 'Image could not be processed safely.',
            ]);
        }

        $width = \imagesx($image);
        $height = \imagesy($image);
        if ($width <= 0 || $height <= 0 || $width > 5000 || $height > 5000) {
            \imagedestroy($image);

            throw ValidationException::withMessages([
                'avatar' => 'Image dimensions are not supported.',
            ]);
        }

        // Normalize very large images before saving.
        $maxDimension = 1024;
        if (max($width, $height) > $maxDimension) {
            $ratio = $maxDimension / max($width, $height);
            $targetWidth = max(1, (int) round($width * $ratio));
            $targetHeight = max(1, (int) round($height * $ratio));
            $resized = \imagecreatetruecolor($targetWidth, $targetHeight);
            if ($resized === false) {
                \imagedestroy($image);

                throw ValidationException::withMessages([
                    'avatar' => 'Image could not be resized safely.',
                ]);
            }

            \imagealphablending($resized, false);
            \imagesavealpha($resized, true);
            \imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            \imagedestroy($image);
            $image = $resized;
        }

        $supportsWebp = function_exists('imagewebp');
        $extension = $supportsWebp ? 'webp' : 'png';
        $filename = Str::uuid()->toString().'.'.$extension;
        $diskPath = 'profile-avatars/'.$filename;
        $absolutePath = storage_path('app/public/'.$diskPath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        $saved = $supportsWebp
            ? \imagewebp($image, $absolutePath, 85)
            : \imagepng($image, $absolutePath, 6);

        \imagedestroy($image);

        if (! $saved) {
            throw ValidationException::withMessages([
                'avatar' => 'Failed to save sanitized image.',
            ]);
        }

        return 'storage/'.$diskPath;
    }

    private function deleteAvatarFile(?string $avatarUrl): void
    {
        if (blank($avatarUrl) || ! str_starts_with((string) $avatarUrl, 'storage/profile-avatars/')) {
            return;
        }

        $relativePath = Str::after((string) $avatarUrl, 'storage/');
        Storage::disk('public')->delete($relativePath);
    }
}
