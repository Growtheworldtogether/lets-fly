<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Stores uploaded images. PNG, JPG and WebP only (never SVG), size-limited, re-encoded and given a generated name. */
final class MediaStore
{
    public const MAX_BYTES = 4 * 1024 * 1024;
    private const TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];

    public function store(UploadedFile $file, string $field, ?string $alt = null, ?User $by = null, bool $square = false): Media
    {
        if (! $file->isValid() || $file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages([$field => 'The image must be 4 MB or smaller.']);
        }
        $path = $file->getRealPath();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $info = @getimagesize($path);
        if (! isset(self::TYPES[$mime]) || $info === false || ($info['mime'] ?? null) !== $mime) {
            throw ValidationException::withMessages([$field => 'Use a PNG, JPG or WebP image. SVG and other formats are not accepted.']);
        }
        [$w, $h] = $info;
        if ($w > 6000 || $h > 6000 || $w < 16 || $h < 16) {
            throw ValidationException::withMessages([$field => 'The image must be between 16 and 6000 pixels on each side.']);
        }
        if ($square && abs($w - $h) > max($w, $h) * 0.05) {
            throw ValidationException::withMessages([$field => 'This image must be square.']);
        }

        // Re-encode through GD: drops metadata (including location data) and anything hidden inside the file.
        $img = @imagecreatefromstring((string) file_get_contents($path));
        if (! $img) {
            throw ValidationException::withMessages([$field => 'That image could not be read.']);
        }
        imagealphablending($img, false);
        imagesavealpha($img, true);
        ob_start();
        match ($mime) { 'image/png' => imagepng($img, null, 6), 'image/jpeg' => imagejpeg($img, null, 88), default => imagewebp($img, null, 88) };
        $data = (string) ob_get_clean();
        imagedestroy($img);

        $name = Str::random(40).'.'.self::TYPES[$mime];
        Storage::disk('local')->put('media/'.$name, $data);

        return Media::create([
            'filename' => $name,
            'original_name' => Str::limit(preg_replace('/[^\w. -]+/', '', $file->getClientOriginalName()) ?: 'image', 120, ''),
            'mime' => $mime,
            'size' => strlen($data),
            'width' => $w,
            'height' => $h,
            'alt' => $alt !== null ? Str::limit(strip_tags($alt), 200, '') : null,
            'uploaded_by' => $by?->id,
        ]);
    }
}
