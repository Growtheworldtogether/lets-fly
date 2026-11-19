@extends('layouts.app')
@section('title', brand('vehicle'))
@section('mainclass', 'wide')
@section('content')
<div class="page">
  @if ($hero)
    <section class="hero">
      <p class="eyebrow">{{ brand('name') }}</p>
      <h1>{{ brand('vehicle') }}</h1>
      <p class="lead">{{ $hero->summary }}</p>
      @php($s = $hero)
      @include('member._media')
      @if ($hero->body)<div class="prose">@foreach (preg_split('/\n\s*\n/', trim($hero->body)) as $p)<p>{{ $p }}</p>@endforeach</div>@endif
    </section>
  @endif
  <div class="cards">
    @foreach ($sections as $c)
      <a class="card" href="{{ route('vehicle.show', $c->slug) }}">
        <span class="eyebrow">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
        <strong>{{ $c->title }}</strong>
        <span class="hint">{{ $c->summary }}</span>
      </a>
    @endforeach
  </div>
</div>
@endsection
