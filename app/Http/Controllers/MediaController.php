<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\PageSection;
use Illuminate\Support\Facades\Storage;

/** Serves uploaded images and videos from private storage to signed-in users only. */
class MediaController extends Controller
{
    public function show(string $filename)
    {
        $media = Media::where('filename', $filename)->firstOrFail();
        abort_unless(Storage::disk('local')->exists('media/'.$filename), 404);

        return response(Storage::disk('local')->get('media/'.$filename), 200, [
            'Content-Type' => $media->mime,
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function font(string $filename)
    {
        \App\Models\Font::where('filename', $filename)->firstOrFail();
        abort_unless(Storage::disk('local')->exists('fonts/'.$filename), 404);

        return response(Storage::disk('local')->get('fonts/'.$filename), 200, [
            'Content-Type' => 'font/woff2',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Range requests are handled by the file response, so the video can be scrubbed. */
    public function video(string $slug)
    {
        $s = PageSection::where('slug', $slug)->where('active', true)->firstOrFail();
        abort_unless($s->video_path && Storage::disk('local')->exists($s->video_path), 404);
        $path = Storage::disk('local')->path($s->video_path);

        return response()->file($path, [
            'Content-Type' => str_ends_with($path, '.webm') ? 'video/webm' : 'video/mp4',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
