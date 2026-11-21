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

    /** The styles for everything behind the sign-in; the public stylesheet only covers the code page. */
    public function memberCss()
    {
        return response()->file(resource_path('css/member.css'), [
            'Content-Type' => 'text/css; charset=utf-8',
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** The built-in vehicle pictures live outside the public folder, so only signed-in members can fetch them. */
    public function art(string $name)
    {
        $path = resource_path('vehicle-art/'.$name);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** The two logos load on the code page too, so they are public, but only while they are set as a logo. */
    public function logo(string $filename)
    {
        abort_unless(\App\Services\Logos::isLogoFile($filename), 404);
        $media = Media::where('filename', $filename)->firstOrFail();
        abort_unless(Storage::disk('local')->exists('media/'.$filename), 404);

        return response(Storage::disk('local')->get('media/'.$filename), 200, [
            'Content-Type' => $media->mime,
            'Cache-Control' => 'public, max-age=86400',
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

        $response = response()->file($path, [
            'Content-Type' => str_ends_with($path, '.webm') ? 'video/webm' : 'video/mp4',
            'X-Content-Type-Options' => 'nosniff',
            'Vary' => 'Cookie',
        ]);
        // Only the signed-in browser may keep this: a file response is "public" unless told otherwise, and a shared cache
        // (a host's page cache, a proxy) must never hand a members-only video to someone who is not signed in.
        $response->setPrivate();
        $response->setMaxAge(3600);

        return $response;
    }
}
