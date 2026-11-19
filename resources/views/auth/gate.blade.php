@extends('layouts.app')
@section('bodyclass', 'studio')
@section('content')
<section class="gate">
  <p class="eyebrow">Invitation only</p>
  <h1>Enter your invite code</h1>
  <form method="post" action="{{ route('gate.enter') }}" autocomplete="off" id="gate-form">@csrf
    <label class="sr" for="code">Invite code</label>
    <input id="code" name="code" class="field mono" placeholder="XXXX-XXXX-XXXX-XXXX" required autofocus maxlength="40" spellcheck="false" autocapitalize="characters">
    <noscript><button class="btn solid" type="submit">Continue</button></noscript>
    <div class="slide" id="slide" hidden>
      <span class="slide-label">Slide to enter the future</span>
      <button type="button" class="slide-handle" id="slide-handle" aria-label="Slide to enter the future. Press Enter to submit instead."><span aria-hidden="true">&rarr;</span></button>
    </div>
  </form>
</section>
<script>
(function () {
  var form = document.getElementById('gate-form'), slide = document.getElementById('slide'), handle = document.getElementById('slide-handle'), input = document.getElementById('code');
  slide.hidden = false;
  var dragging = false, startX = 0, pos = 0, max = 0, done = false;
  function limits() { max = slide.clientWidth - handle.offsetWidth - 2; }
  function move(x) { pos = Math.max(0, Math.min(max, x)); handle.style.transform = 'translateX(' + pos + 'px)'; slide.style.setProperty('--fill', (pos + handle.offsetWidth) + 'px'); }
  function submit() {
    if (done) return;
    if (!input.value.trim()) { input.focus(); input.classList.add('shake'); setTimeout(function () { input.classList.remove('shake'); }, 400); reset(); return; }
    done = true; move(max); slide.classList.add('done'); form.submit();
  }
  function reset() { slide.classList.add('back'); move(0); setTimeout(function () { slide.classList.remove('back'); }, 250); }
  handle.addEventListener('pointerdown', function (e) { if (done) return; limits(); dragging = true; startX = e.clientX - pos; handle.setPointerCapture(e.pointerId); slide.classList.remove('back'); e.preventDefault(); });
  handle.addEventListener('pointermove', function (e) { if (dragging) move(e.clientX - startX); });
  function end() { if (!dragging) return; dragging = false; if (pos >= max * 0.92) submit(); else reset(); }
  handle.addEventListener('pointerup', end); handle.addEventListener('pointercancel', end);
  handle.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); limits(); submit(); } });
  window.addEventListener('resize', function () { limits(); if (!done) move(0); });
})();
</script>
@endsection
