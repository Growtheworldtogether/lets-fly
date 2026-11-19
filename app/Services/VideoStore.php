<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Stores an uploaded MP4 or WebM video in private storage under a random name. */
final class VideoStore
{
    public const MAX_BYTES = 250 * 1024 * 1024;
    private const TYPES = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];

    public function store(UploadedFile $file, string $field = 'video'): string
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([$field => 'The video did not upload. It may be larger than the server allows (upload_max_filesize and post_max_size in php.ini).']);
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages([$field => 'The video must be 250 MB or smaller.']);
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        if (! isset(self::TYPES[$mime])) {
            throw ValidationException::withMessages([$field => 'Use an MP4 or WebM video.']);
        }
        $path = 'private/videos/'.Str::random(40).'.'.self::TYPES[$mime];
        Storage::disk('local')->putFileAs('private/videos', $file, basename($path));

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && str_starts_with($path, 'private/videos/')) {
            Storage::disk('local')->delete($path);
        }
    }
}
