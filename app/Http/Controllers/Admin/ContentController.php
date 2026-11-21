<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use App\Services\Audit;
use App\Services\MediaStore;
use App\Services\ChunkedVideo;
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

    /** Big videos arrive in small pieces: begin, then every piece, then finish. Answers are JSON for the edit page. */
    public function videoBegin(Request $request, PageSection $section, ChunkedVideo $chunks)
    {
        return $this->json(function () use ($request, $section, $chunks) {
            $d = $request->validate(['size' => ['required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:200']]);

            return ['id' => $chunks->begin($request->user(), $section, (int) $d['size'], $d['name'])];
        });
    }

    public function videoChunk(Request $request, PageSection $section, ChunkedVideo $chunks)
    {
        return $this->json(function () use ($request, $section, $chunks) {
            $chunks->receive((string) $request->input('id'), $request->user(), $section, (int) $request->input('offset'), $request->file('chunk'));

            return ['ok' => true];
        });
    }

    public function videoFinish(Request $request, PageSection $section, ChunkedVideo $chunks, VideoStore $videos)
    {
        return $this->json(function () use ($request, $section, $chunks, $videos) {
            $path = $chunks->finish((string) $request->input('id'), $request->user(), $section);
            $videos->delete($section->video_path);
            $section->update(['video_path' => $path, 'video_url' => null]);
            Audit::record('content.updated', $section, null, ['slug' => $section->slug, 'video' => 'uploaded in pieces'], $request->user()->id);

            return ['ok' => true];
        });
    }

    private function json(\Closure $work)
    {
        try {
            return response()->json($work());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = (string) collect($e->errors())->flatten()->first();
            if ($msg === 'too_big') {
                return response()->json(['error' => 'The server refused a piece that large.', 'code' => 'too_big'], 422);
            }
            if (str_starts_with($msg, 'incomplete:')) {
                return response()->json(['error' => 'Some pieces did not arrive.', 'code' => 'incomplete', 'next' => (int) substr($msg, 11)], 409);
            }

            return response()->json(['error' => $msg], 422);
        } catch (\Throwable $e) {
            // Only staff reach these addresses, so the real cause is shown instead of a bare "Server Error".
            report($e);

            return response()->json(['error' => 'The server could not handle the upload ('.class_basename($e).': '.\Illuminate\Support\Str::limit($e->getMessage(), 200).').', 'code' => 'server'], 500);
        }
    }
}
