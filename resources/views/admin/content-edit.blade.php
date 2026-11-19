@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page">@include('admin._nav')
  <h1>{{ $s->title }}</h1>
  <form method="post" action="{{ route('admin.content.update', $s->slug) }}" enctype="multipart/form-data" class="stack">@csrf
    <label for="title">Title</label><input id="title" name="title" class="field" required maxlength="120" value="{{ old('title', $s->title) }}">
    <label for="summary">Short line</label><input id="summary" name="summary" class="field" maxlength="400" value="{{ old('summary', $s->summary) }}">
    <label for="body">Text</label><textarea id="body" name="body" class="field area tall" maxlength="20000">{{ old('body', $s->body) }}</textarea>
    <p class="hint">Leave a blank line between paragraphs.</p>
    <label for="sort">Order (lower shows first)</label><input id="sort" name="sort" type="number" min="0" max="999" class="field" required value="{{ old('sort', $s->sort) }}">
    <label class="check"><input type="checkbox" name="active" value="1" @checked(old('active', $s->active))> Show this section to members</label>

    <h2>Picture</h2>
    @if ($s->image)<p class="hint">Current picture uploaded. <label class="check inl"><input type="checkbox" name="remove_image" value="1"> Remove it</label></p>@endif
    <input name="image" type="file" accept="image/png,image/jpeg,image/webp" class="field file"><p class="hint">PNG, JPG or WebP, up to 4 MB.</p>

    <h2>Video</h2>
    @if ($s->video_path)<p class="hint">An uploaded video is in place. <label class="check inl"><input type="checkbox" name="remove_video" value="1"> Remove it</label></p>@endif
    <input name="video" type="file" accept="video/mp4,video/webm" class="field file"><p class="hint">MP4 or WebM, up to 250 MB. Your server's upload limit may be lower (php.ini: upload_max_filesize and post_max_size).</p>
    <label for="video_url">Or a link to a hosted video</label><input id="video_url" name="video_url" type="url" class="field" placeholder="https://" value="{{ old('video_url', $s->video_url) }}">
    <p class="hint">YouTube and Vimeo links play in the page. Any other https link must point straight at an MP4 or WebM file.</p>
    <label for="poster">Video cover image (optional)</label>
    @if ($s->poster)<p class="hint">A cover is in place. <label class="check inl"><input type="checkbox" name="remove_poster" value="1"> Remove it</label></p>@endif
    <input id="poster" name="poster" type="file" accept="image/png,image/jpeg,image/webp" class="field file">

    <button class="btn solid" type="submit">Save</button>
  </form>
</div>
@endsection
