{{-- Full-bleed picture behind a section. Without an upload, a studio-lit placeholder shows. --}}
@if ($s->image)
  <img class="v-img" src="{{ $s->image->url() }}" alt="{{ $s->image->alt ?: $s->title }}" loading="{{ $eager ?? false ? 'eager' : 'lazy' }}">
@elseif (! empty($fallback))
  <img class="v-img v-fallback" src="{{ asset($fallback) }}" alt="" loading="{{ $eager ?? false ? 'eager' : 'lazy' }}">
@else
  <div class="v-ph" role="img" aria-label="Image placeholder"><span>Image placeholder: {{ $s->title }}</span></div>
@endif
