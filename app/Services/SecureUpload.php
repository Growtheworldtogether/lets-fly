<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Private uploads for check images and identity documents. Files go to the private disk under a random
 * name and are only ever served through an authorised controller. Images are re-encoded to drop metadata.
 * PDFs are accepted only where the caller allows them and are checked by their header.
 */
final class SecureUpload
{
    public const MAX_BYTES = 8 * 1024 * 1024;
    private const IMAGES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];

    /** @return array{path: string, name: string, mime: string, size: int} */
    public function store(UploadedFile $file, string $dir, string $field, bool $allowPdf = false): array
    {
        if (! $file->isValid() || $file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages([$field => 'The file must be 8 MB or smaller.']);
        }
        $real = $file->getRealPath();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($real);
        $name = Str::limit(preg_replace('/[^\w. -]+/', '', $file->getClientOriginalName()) ?: 'file', 100, '');

        if ($allowPdf && $mime === 'application/pdf' && str_starts_with((string) file_get_contents($real, false, null, 0, 5), '%PDF-')) {
            $data = (string) file_get_contents($real);
            $ext = 'pdf';
        } else {
            $info = @getimagesize($real);
            if (! isset(self::IMAGES[$mime]) || $info === false || ($info['mime'] ?? null) !== $mime) {
                throw ValidationException::withMessages([$field => $allowPdf ? 'Use a PNG, JPG, WebP or PDF file.' : 'Use a clear photo or scan as a PNG, JPG or WebP file.']);
            }
            if ($info[0] > 8000 || $info[1] > 8000 || $info[0] < 200 || $info[1] < 200) {
                throw ValidationException::withMessages([$field => 'The image is too small or too large. Take the photo again closer to the page.']);
            }
            $img = @imagecreatefromstring((string) file_get_contents($real));
            if (! $img) {
                throw ValidationException::withMessages([$field => 'That image could not be read.']);
            }
            ob_start();
            match ($mime) { 'image/png' => imagepng($img, null, 6), 'image/jpeg' => imagejpeg($img, null, 90), default => imagewebp($img, null, 90) };
            $data = (string) ob_get_clean();
            imagedestroy($img);
            $ext = self::IMAGES[$mime];
        }

        $path = 'private/'.$dir.'/'.Str::random(40).'.'.$ext;
        Storage::disk('local')->put($path, $data);

        return ['path' => $path, 'name' => $name, 'mime' => $ext === 'pdf' ? 'application/pdf' : $mime, 'size' => strlen($data)];
    }

    public function path(string $path): string
    {
        return Storage::disk('local')->path($path);
    }
}
