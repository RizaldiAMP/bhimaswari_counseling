<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Models\CounselorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Intervention\Image\ImageManager;

class CounselorProfileController extends Controller
{
    public function edit(Request $request)
    {
        $profile = $request->user()->counselorProfile;

        return Inertia::render('Counselor/Profile', [
            'profile' => $profile,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:2000'],
            'specializations' => ['nullable', 'array'],
            'specializations.*' => ['string', 'max:100'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'education' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $profile = $request->user()->counselorProfile;

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($profile->photo_path) {
                Storage::disk('public')->delete($profile->photo_path);
            }

            $file = $request->file('photo');
            $filename = Str::uuid() . '.webp';

            // Convert to WebP using GD
            $sourcePath = $file->getRealPath();
            $mime = $file->getMimeType();

            $sourceImage = match ($mime) {
                'image/jpeg', 'image/jpg' => imagecreatefromjpeg($sourcePath),
                'image/png' => imagecreatefrompng($sourcePath),
                'image/webp' => imagecreatefromwebp($sourcePath),
                default => imagecreatefromjpeg($sourcePath),
            };

            if ($sourceImage) {
                // Preserve transparency for PNG
                imagepalettetotruecolor($sourceImage);
                imagealphablending($sourceImage, true);
                imagesavealpha($sourceImage, true);

                $directory = storage_path('app/public/counselor-photos');
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                $webpPath = $directory . '/' . $filename;
                imagewebp($sourceImage, $webpPath, 95); // quality 95% for HD
                imagedestroy($sourceImage);

                $profile->photo_path = 'counselor-photos/' . $filename;
            } else {
                // Fallback: store original if conversion fails
                $path = $file->storeAs('counselor-photos', Str::uuid() . '.' . $file->getClientOriginalExtension(), 'public');
                $profile->photo_path = $path;
            }
        }

        $profile->bio = $validated['bio'] ?? $profile->bio;
        $profile->specializations = $validated['specializations'] ?? $profile->specializations;
        $profile->save();

        // Clear landing page cache so updated photo/bio is reflected immediately
        Cache::forget('landing_page_data');

        return back()->with('success', 'Profil publik berhasil diperbarui.');
    }
}
