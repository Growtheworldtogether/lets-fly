{{-- The credit union's logo: an uploaded image when there is one, otherwise the mark and a text wordmark.
     $variant is 'light' (for light backgrounds) or 'dark' (for dark ones). $caption adds a small line under the name. --}}
@php($logo = \App\Services\Brand::logoUrl($variant ?? 'light'))
@if ($logo)
    <img class="brand-logo" src="{{ $logo }}" alt="{{ brand('name') }}">
    @isset($caption)<small class="brand-caption">{{ $caption }}</small>@endisset
@else
    @include('partials.brand-mark')
    <span class="brand-name">{{ brand('short_name') }}<small>{{ strtoupper($caption ?? brand('descriptor')) }}</small></span>
@endif
