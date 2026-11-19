{{-- A section's picture and video. Without an upload, a studio-lit placeholder shows. --}}
@php($kind = $s->videoKind())
@if ($s->hasVideo())
  <div class="media video">
    @if ($kind === 'upload')
      <video controls preload="metadata" playsinline @if ($s->poster) poster="{{ $s->poster->url() }}" @endif><source src="{{ route('vehicle.video', $s->slug) }}"></video>
    @elseif ($kind === 'file')
      <video controls preload="metadata" playsinline @if ($s->poster) poster="{{ $s->poster->url() }}" @endif><source src="{{ $s->video_url }}"></video>
    @else
      <iframe src="{{ $s->embedUrl() }}" title="{{ $s->title }} video" allow="fullscreen; picture-in-picture" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
    @endif
  </div>
@endif
@if ($s->image)
  <div class="media"><img src="{{ $s->image->url() }}" alt="{{ $s->image->alt ?: $s->title }}" loading="lazy"></div>
@elseif (! $s->hasVideo())
  <div class="media ph" role="img" aria-label="Image placeholder"><span>Image placeholder: {{ $s->title }}</span></div>
@endif
