<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\Brand;
use Illuminate\Support\Facades\Storage;

/** Serves uploaded images from private storage, and the favicon (uploaded, or drawn from the brand colours). */
class MediaController extends Controller
{
    public function show(string $filename)
    {
        $media = Media::where('filename', $filename)->firstOrFail();
        abort_unless(Storage::disk('local')->exists('media/'.$filename), 404);

        return response(Storage::disk('local')->get('media/'.$filename), 200, [
            'Content-Type' => $media->mime,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function favicon()
    {
        if ($id = Brand::get('favicon_id')) {
            $media = Media::find($id);
            if ($media && Storage::disk('local')->exists('media/'.$media->filename)) {
                return response(Storage::disk('local')->get('media/'.$media->filename), 200, ['Content-Type' => $media->mime, 'Cache-Control' => 'public, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
            }
        }
        if (is_file(public_path('images/favicon.png'))) {
            return response(file_get_contents(public_path('images/favicon.png')), 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=3600']);
        }
        $b = Brand::all();
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 46 46"><circle cx="23" cy="23" r="23" fill="'.$b['brand_color'].'"/><circle cx="23" cy="26" r="9" fill="'.$b['highlight_color'].'"/><path d="M6 36h34" stroke="'.$b['highlight_color'].'" stroke-width="3" stroke-linecap="round"/></svg>';

        return response($svg, 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'public, max-age=3600']);
    }
}
