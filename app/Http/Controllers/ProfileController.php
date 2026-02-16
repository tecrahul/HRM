<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $request->user()?->loadMissing('profile');

        return view('profile.edit');
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

        $user->profile()->updateOrCreate([], [
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

        $imageContent = @file_get_contents((string) $sourcePath);
        $image = $imageContent !== false ? @imagecreatefromstring($imageContent) : false;
        if ($image === false) {
            throw ValidationException::withMessages([
                'avatar' => 'Image could not be processed safely.',
            ]);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width <= 0 || $height <= 0 || $width > 5000 || $height > 5000) {
            imagedestroy($image);

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
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($resized === false) {
                imagedestroy($image);

                throw ValidationException::withMessages([
                    'avatar' => 'Image could not be resized safely.',
                ]);
            }

            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            imagedestroy($image);
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
            ? imagewebp($image, $absolutePath, 85)
            : imagepng($image, $absolutePath, 6);

        imagedestroy($image);

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
