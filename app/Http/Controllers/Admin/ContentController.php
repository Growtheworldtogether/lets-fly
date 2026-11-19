<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use App\Services\Audit;
use App\Services\MediaStore;
use App\Services\VideoStore;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function index()
    {
        PageSection::ensureDefaults();

        return view('admin.content', ['sections' => PageSection::orderBy('sort')->get()]);
    }

    public function edit(PageSection $section)
    {
        return view('admin.content-edit', ['s' => $section]);
    }

    public function update(Request $request, PageSection $section, MediaStore $images, VideoStore $videos)
    {
        $d = $request->validate([
            'title' => ['required', 'string', 'max:120'], 'summary' => ['nullable', 'string', 'max:400'],
            'body' => ['nullable', 'string', 'max:20000'], 'sort' => ['required', 'integer', 'between:0,999'],
            'video_url' => ['nullable', 'url:https', 'max:500'],
            'image' => ['nullable', 'file'], 'poster' => ['nullable', 'file'], 'video' => ['nullable', 'file'],
        ]);
        $u = $request->user();
        $new = [
            'title' => $d['title'], 'summary' => $d['summary'] ?? null, 'body' => $d['body'] ?? null,
            'sort' => $d['sort'], 'active' => $request->boolean('active'),
        ];
        if ($request->hasFile('image')) {
            $new['image_id'] = $images->store($request->file('image'), 'image', $d['title'], $u)->id;
        } elseif ($request->boolean('remove_image')) {
            $new['image_id'] = null;
        }
        if ($request->hasFile('poster')) {
            $new['poster_id'] = $images->store($request->file('poster'), 'poster', $d['title'], $u)->id;
        } elseif ($request->boolean('remove_poster')) {
            $new['poster_id'] = null;
        }
        if ($request->hasFile('video')) {
            $videos->delete($section->video_path);
            $new['video_path'] = $videos->store($request->file('video'));
            $new['video_url'] = null;
        } elseif ($request->boolean('remove_video')) {
            $videos->delete($section->video_path);
            $new['video_path'] = null;
            $new['video_url'] = null;
        } elseif (array_key_exists('video_url', $d) && $d['video_url'] !== ($section->video_url)) {
            $videos->delete($section->video_path);
            $new['video_path'] = null;
            $new['video_url'] = $d['video_url'];
        }
        $section->update($new);
        Audit::record('content.updated', $section, null, ['slug' => $section->slug], $u->id);

        return redirect()->route('admin.content')->with('toast', $section->title.' saved.');
    }
}
