{{-- The vehicle name: the uploaded vehicle logo when there is one, otherwise styled text. $tag, $text, optional $class --}}
@php($logo = brand_logo('vehicle'))
@if ($logo)<{{ $tag }} class="{{ $class ?? '' }} has-logo"><img class="v-logo" src="{{ $logo }}" alt="{{ $text }}"></{{ $tag }}>
@else<{{ $tag }}@if (! empty($class)) class="{{ $class }}"@endif>{{ $text }}</{{ $tag }}>
@endif
