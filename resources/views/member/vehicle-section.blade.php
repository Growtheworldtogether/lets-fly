@extends('layouts.app')
@section('title', $s->title.' · '.brand('vehicle'))
@section('mainclass', 'wide')
@section('content')
<div class="page narrow">
  <p class="eyebrow"><a href="{{ route('vehicle') }}">{{ brand('vehicle') }}</a></p>
  <h1>{{ $s->title }}</h1>
  @if ($s->summary)<p class="lead">{{ $s->summary }}</p>@endif
  @include('member._media')
  @if ($s->body)<div class="prose">@foreach (preg_split('/\n\s*\n/', trim($s->body)) as $p)<p>{!! nl2br(e($p)) !!}</p>@endforeach</div>@endif
  <nav class="pager" aria-label="Sections">
    @if ($prev)<a href="{{ route('vehicle.show', $prev->slug) }}">&larr; {{ $prev->title }}</a>@else<span></span>@endif
    @if ($next)<a href="{{ route('vehicle.show', $next->slug) }}">{{ $next->title }} &rarr;</a>@endif
  </nav>
</div>
@endsection
