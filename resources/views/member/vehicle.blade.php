@extends('layouts.app')
@section('title', brand('vehicle'))
@section('mainclass', 'bleed')
@section('content')
@php($intro = $hero?->parts())
@if ($hero)
<section class="v-hero" id="top">
  <div class="v-bg">@php($s = $hero)@include('member._bg', ['eager' => true])</div>
  <div class="v-hero-in">
    <p class="eyebrow">{{ brand('name') }}</p>
    <h1>{{ $hero->title }}</h1>
    @if ($hero->summary)<p class="v-lead">{{ $hero->summary }}</p>@endif
    <div class="v-cta">
      <a class="btn solid" href="{{ route('dashboard') }}">Reserve my allocation</a>
      <a class="btn" href="#specs">Specifications</a>
    </div>
  </div>
  @if ($intro['facts'])
    <dl class="v-stats">@foreach (array_slice($intro['facts'], 0, 4) as [$k, $v])<div><dt>{{ $k }}</dt><dd>{{ $v }}</dd></div>@endforeach</dl>
  @endif
</section>

<nav class="v-jump" aria-label="Sections"><div>@foreach ($sections as $c)<a href="#{{ $c->slug }}">{{ $c->title }}</a>@endforeach</div></nav>

<section class="v-intro">
  @if ($intro['headline'])<h2>{{ $intro['headline'] }}</h2>@endif
  @foreach ($intro['paras'] as $p)<p>{{ $p }}</p>@endforeach
  @php($s = $hero)@include('member._video')
</section>
@endif

@foreach ($sections as $s)
  @php($p = $s->parts())
  @if ($s->slug === 'specs')
    <section class="v-specs" id="specs">
      <div class="v-specs-in">
        <h2>{{ $s->title }}</h2>
        @if ($s->summary)<p class="v-sum">{{ $s->summary }}</p>@endif
        @foreach ($p['paras'] as $para)<p class="v-p">{{ $para }}</p>@endforeach
        @include('member._video')
        <div class="v-groups">
          @foreach ($p['groups'] as [$name, $rows])
            <div class="v-group"><h3>{{ $name }}</h3><dl>@foreach ($rows as [$k, $v])<div><dt>{{ $k }}</dt><dd>{{ $v }}</dd></div>@endforeach</dl></div>
          @endforeach
        </div>
      </div>
    </section>
  @else
    <section class="v-sec {{ ['left', 'right', 'center'][$loop->index % 3] }}" id="{{ $s->slug }}">
      <div class="v-bg">@include('member._bg')</div>
      <div class="v-copy">
        <p class="eyebrow">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }} · {{ $s->title }}</p>
        <h2>{{ $p['headline'] ?? $s->title }}</h2>
        @foreach ($p['paras'] as $para)<p>{{ $para }}</p>@endforeach
        @if ($p['facts'])<dl class="v-facts">@foreach ($p['facts'] as [$k, $v])<div><dt>{{ $k }}</dt><dd>{{ $v }}</dd></div>@endforeach</dl>@endif
        @include('member._video')
      </div>
    </section>
  @endif
@endforeach

<section class="v-end">
  <h2>Your place in the first group</h2>
  <p>Reserve your allocation with a deposit. The balance is due within 10 days after we verify it.</p>
  <a class="btn solid" href="{{ route('dashboard') }}">Reserve my allocation</a>
  <p class="v-note">Specifications are targets for the pioneer edition and may change before delivery. Where and how you may fly depends on the aviation rules of your country.</p>
</section>
<script>(function(){var l=document.querySelectorAll('.v-jump a');if(!('IntersectionObserver' in window)||!l.length)return;var m={};l.forEach(function(a){m[a.getAttribute('href').slice(1)]=a});var o=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){l.forEach(function(a){a.classList.remove('on')});var a=m[e.target.id];if(a){a.classList.add('on');a.scrollIntoView({block:'nearest',inline:'center'})}}})},{rootMargin:'-45% 0px -50% 0px'});document.querySelectorAll('.v-sec,.v-specs').forEach(function(x){o.observe(x)})})();</script>
@endsection
