@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page">@include('admin._nav')
  <h1>{{ $s->title }}</h1>
  <form id="content-form" method="post" action="{{ route('admin.content.update', $s->slug) }}" enctype="multipart/form-data" class="stack">@csrf
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
    <input name="video" type="file" accept="video/mp4,video/webm" class="field file"><p class="hint">MP4 or WebM, up to 250 MB. Big videos are sent in small pieces, so keep this page open until the bar reaches 100%. A smaller file (compressed to about 20 to 60 MB) also plays faster for members.</p>
    <div id="vup" hidden role="status" aria-live="polite" style="margin:4px 0 8px"><div style="height:10px;background:#1d1d1f;border-radius:4px;overflow:hidden"><i id="vup-bar" style="display:block;height:100%;width:0;background:#d7a970"></i></div><p class="hint" id="vup-text" style="margin-top:6px"></p></div>
    <label for="video_url">Or a link to a hosted video</label><input id="video_url" name="video_url" type="url" class="field" placeholder="https://" value="{{ old('video_url', $s->video_url) }}">
    <p class="hint">YouTube and Vimeo links play in the page. Any other https link must point straight at an MP4 or WebM file.</p>
    <label for="poster">Video cover image (optional)</label>
    @if ($s->poster)<p class="hint">A cover is in place. <label class="check inl"><input type="checkbox" name="remove_poster" value="1"> Remove it</label></p>@endif
    <input id="poster" name="poster" type="file" accept="image/png,image/jpeg,image/webp" class="field file">

    <button class="btn solid" type="submit">Save</button>
  </form>
</div>

<script>
(function () {
  var form = document.getElementById('content-form');
  var input = form && form.querySelector('input[name=video]');
  if (!input || !window.fetch || !File.prototype.slice) return;
  var box = document.getElementById('vup'), bar = document.getElementById('vup-bar'), text = document.getElementById('vup-text');
  var token = form.querySelector('input[name=_token]').value;
  var urls = { begin: @json(route('admin.content.video.begin', $s->slug)), chunk: @json(route('admin.content.video.chunk', $s->slug)), finish: @json(route('admin.content.video.finish', $s->slug)) };
  var busy = false;

  function wait(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }
  function mb(n) { return (n / 1048576).toFixed(n > 10485760 ? 0 : 1); }
  function post(url, body) {
    return fetch(url, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { status: r.status, body: j }; }); });
  }
  function form1(o) { var f = new FormData(); Object.keys(o).forEach(function (k) { f.append(k, o[k]); }); return f; }
  function show(done, total) {
    var pct = total ? Math.floor(done * 100 / total) : 0;
    bar.style.width = pct + '%';
    text.textContent = 'Uploading the video: ' + pct + '% (' + mb(done) + ' of ' + mb(total) + ' MB). Keep this page open.';
  }

  async function upload(file) {
    var b = await post(urls.begin, form1({ size: file.size, name: file.name }));
    if (b.status !== 200) throw new Error(b.body.error || (b.body.message) || 'The upload could not start.');
    var id = b.body.id, size = 2 * 1024 * 1024, offset = 0, tries = 0, rounds = 0;
    show(0, file.size);
    while (true) {
      while (offset < file.size) {
        var end = Math.min(offset + size, file.size);
        var r;
        try { r = await post(urls.chunk, form1({ id: id, offset: offset, chunk: file.slice(offset, end), })); }
        catch (e) { r = { status: 0, body: {} }; }
        if (r.status === 200) { offset = end; tries = 0; show(offset, file.size); continue; }
        if ((r.status === 413 || (r.status === 422 && r.body.code === 'too_big')) && size > 128 * 1024) { size = Math.max(128 * 1024, Math.floor(size / 2)); continue; }
        if (r.status === 429) { await wait(6000); continue; }
        if (r.status === 419 || r.status === 401 || r.status === 404) throw new Error('Your session timed out. Reload the page and sign in again.');
        if (r.status === 422 && r.body.error && r.body.code !== 'too_big') throw new Error(r.body.error);
        if (++tries > 5) throw new Error('The connection kept failing. Check your internet and try again.');
        await wait(Math.min(15000, 1000 * Math.pow(2, tries)));
      }
      var f = await post(urls.finish, form1({ id: id }));
      if (f.status === 200) return;
      if (f.status === 409 && ++rounds < 4) { offset = f.body.next || 0; show(offset, file.size); continue; }
      throw new Error(f.body.error || 'The video could not be finished.');
    }
  }

  form.addEventListener('submit', function (e) {
    var file = input.files[0];
    if (!file) return;
    e.preventDefault();
    if (busy) return;
    busy = true;
    var btn = form.querySelector('button[type=submit]');
    btn.disabled = true; box.hidden = false;
    upload(file).then(function () {
      text.textContent = 'The video is uploaded. Saving…';
      input.value = '';
      var url = form.querySelector('#video_url'); if (url) url.value = '';
      var rm = form.querySelector('input[name=remove_video]'); if (rm) rm.checked = false;
      window.onbeforeunload = null;
      form.submit();
    }).catch(function (err) {
      busy = false; btn.disabled = false; window.onbeforeunload = null;
      bar.style.width = '0'; text.textContent = (err && err.message ? err.message : 'The upload failed.') + ' Choose the video and press Save to try again.';
    });
    window.onbeforeunload = function () { return 'The video is still uploading.'; };
  });
})();
</script>
@endsection
