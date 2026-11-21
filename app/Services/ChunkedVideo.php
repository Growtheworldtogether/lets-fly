<?php

namespace App\Services;

use App\Models\PageSection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Receives a big video in small pieces, so the host's upload limit and time limit never apply to the whole file.
 *
 * begin()   makes a private upload folder and remembers who it is for and how big the file will be
 * receive() stores one piece, named by where it starts in the file (so a piece can be sent again safely)
 * finish()  checks every piece is there, joins them, checks it is really an MP4 or WebM, and attaches it to the section
 */
final class ChunkedVideo
{
    public const MAX_PIECE = 16 * 1024 * 1024;
    private const ROOT = 'private/video-uploads';
    private const STALE_AFTER = 86400;

    public function begin(User $user, PageSection $section, int $size, string $name): string
    {
        $this->prune();
        if ($size < 1 || $size > VideoStore::MAX_BYTES) {
            throw ValidationException::withMessages(['video' => 'The video must be 250 MB or smaller.']);
        }
        if (! preg_match('/\.(mp4|webm)$/i', $name)) {
            throw ValidationException::withMessages(['video' => 'Use an MP4 or WebM video.']);
        }
        $disk = Storage::disk('local');
        File::ensureDirectoryExists($disk->path(self::ROOT));
        // Some hosts switch disk_free_space off; then the space is simply not checked.
        $free = function_exists('disk_free_space') ? @disk_free_space($disk->path(self::ROOT)) : null;
        if (is_numeric($free) && $free > 0 && $free < $size * 1.2 + 20 * 1024 * 1024) {
            throw ValidationException::withMessages(['video' => 'The server does not have enough free disk space for this video.']);
        }
        $id = bin2hex(random_bytes(16));
        File::ensureDirectoryExists($disk->path(self::ROOT.'/'.$id));
        file_put_contents($disk->path(self::ROOT.'/'.$id.'/meta.json'), json_encode([
            'user' => $user->id, 'section' => $section->id, 'size' => $size, 'created' => time(),
        ]));

        return $id;
    }

    public function receive(string $id, User $user, PageSection $section, int $offset, ?UploadedFile $piece): void
    {
        $meta = $this->meta($id, $user, $section);
        if (! $piece || ! $piece->isValid()) {
            throw ValidationException::withMessages(['video' => 'too_big']);
        }
        $len = (int) $piece->getSize();
        if ($len < 1 || $len > self::MAX_PIECE || $offset < 0 || $offset + $len > $meta['size']) {
            throw ValidationException::withMessages(['video' => 'That piece does not fit the video. Start the upload again.']);
        }
        $piece->move(Storage::disk('local')->path(self::ROOT.'/'.$id), sprintf('%012d.part', $offset));
    }

    /** @return array{done: bool, next: int} next = where the missing part starts */
    public function check(string $id, User $user, PageSection $section): array
    {
        $meta = $this->meta($id, $user, $section);
        $next = 0;
        foreach ($this->parts($id) as $offset => $path) {
            if ($offset !== $next) {
                break;
            }
            $next += filesize($path);
        }

        return ['done' => $next === $meta['size'], 'next' => $next];
    }

    /** Joins the pieces and returns the stored path (private/videos/...). */
    public function finish(string $id, User $user, PageSection $section): string
    {
        $state = $this->check($id, $user, $section);
        if (! $state['done']) {
            throw ValidationException::withMessages(['video' => 'incomplete:'.$state['next']]);
        }
        $disk = Storage::disk('local');
        File::ensureDirectoryExists($disk->path('private/videos'));
        $tmp = $disk->path('private/videos/'.Str::random(40).'.joining');
        $out = fopen($tmp, 'wb');
        if (! $out) {
            throw ValidationException::withMessages(['video' => 'The server could not write the video.']);
        }
        foreach ($this->parts($id) as $path) {
            $in = fopen($path, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            unlink($path);
        }
        fclose($out);

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $ext = ['video/mp4' => 'mp4', 'video/webm' => 'webm'][$mime] ?? null;
        if ($ext === null) {
            @unlink($tmp);
            $this->discard($id);
            throw ValidationException::withMessages(['video' => 'Use an MP4 or WebM video.']);
        }
        $name = Str::random(40).'.'.$ext;
        rename($tmp, $disk->path('private/videos/'.$name));
        $this->discard($id);

        return 'private/videos/'.$name;
    }

    public function discard(string $id): void
    {
        if (preg_match('/^[a-f0-9]{32}$/', $id)) {
            File::deleteDirectory(Storage::disk('local')->path(self::ROOT.'/'.$id));
        }
    }

    /** Folders left behind by an upload that was never finished. */
    public function prune(): void
    {
        $root = Storage::disk('local')->path(self::ROOT);
        foreach (glob($root.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (filemtime($dir) < time() - self::STALE_AFTER) {
                File::deleteDirectory($dir);
            }
        }
    }

    /** @return array{user: int, section: int, size: int, created: int} */
    private function meta(string $id, User $user, PageSection $section): array
    {
        $file = Storage::disk('local')->path(self::ROOT.'/'.$id.'/meta.json');
        $meta = preg_match('/^[a-f0-9]{32}$/', $id) && is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        if (! is_array($meta) || $meta['user'] !== $user->id || $meta['section'] !== $section->id) {
            throw ValidationException::withMessages(['video' => 'This upload is no longer open. Start it again.']);
        }
        @touch(dirname($file));

        return $meta;
    }

    /** @return array<int, string> start offset => path, in order */
    private function parts(string $id): array
    {
        $out = [];
        foreach (glob(Storage::disk('local')->path(self::ROOT.'/'.$id).'/*.part') ?: [] as $path) {
            $out[(int) basename($path, '.part')] = $path;
        }
        ksort($out);

        return $out;
    }
}
