@extends('layouts.app')
@section('title', brand('vehicle'))
@section('mainclass', 'bleed')
@section('bodyclass', 'showroom')
@section('content')
@php
  $by = $sections->keyBy('slug');
  $cards = collect(['exterior', 'flight', 'avionics', 'interior', 'power'])->map(fn ($k) => $by[$k] ?? null)->filter();
  $show = fn ($k) => $by[$k] ?? null;
@endphp

{{-- 1. Hero: the name, two buttons, feature badges --}}
@if ($hero)
@php($facts = $hero->parts()['facts'])
<section class="v-hero" id="top">
  <div class="v-bg">@php($s = $hero)@include('member._bg', ['eager' => true, 'fallback' => 'images/hero-default.jpg'])</div>
  <div class="v-hero-in">
    @include('member._vname', ['tag' => 'h1', 'text' => $hero->title])
    <div class="v-cta">
      <a class="btn solid" href="{{ route('dashboard') }}">Reserve now</a>
      <a class="btn" href="#specs">Specifications</a>
    </div>
  </div>
  @if ($facts)
  <dl class="v-badges">@foreach (array_slice($facts, 0, 4) as [$k, $v])<div><dd>{{ $v }}</dd><dt>{{ $k }}</dt></div>@endforeach</dl>
  @endif
</section>
@endif

{{-- 2. Intro: text over a full-width picture --}}
@if ($intro = $show('intro'))
@php($p = $intro->parts())
<section class="v-over top" id="intro">
  <div class="v-bg">@php($s = $intro)@include('member._bg', ['fallback' => 'images/intro-default.png'])</div>
  <div class="v-copy">
    <h2><span>{{ $intro->title }}</span></h2>
    @foreach ($p['paras'] as $para)<p>{{ $para }}</p>@endforeach
    @include('member._video')
  </div>
</section>
@endif

{{-- 3. Carousel of cards: picture above, title and text below --}}
@if ($cards->isNotEmpty())
<section class="v-carousel" aria-label="Highlights">
  <div class="v-track" tabindex="0">
    @foreach ($cards as $s)
    @php($p = $s->parts())
    <article class="v-card" id="{{ $s->slug }}">
      <div class="v-bg">@include('member._bg', ['fallback' => $s->slug === 'exterior' ? 'images/exterior-default.png' : null])</div>
      <div class="v-card-body">
        <h2><span>{{ $p['headline'] ?? $s->title }}</span></h2>
        @foreach ($p['paras'] as $para)<p>{{ $para }}</p>@endforeach
        @include('member._stats', ['facts' => $p['facts']])
        @include('member._video')
      </div>
    </article>
    @endforeach
  </div>
  <div class="v-ctrl"><button type="button" class="prev" aria-label="Previous">&lsaquo;</button><span class="dots">@foreach ($cards as $s)<i></i>@endforeach</span><button type="button" class="next" aria-label="Next">&rsaquo;</button></div>
</section>
@endif

{{-- 4. Text over a full-bleed picture, text at the bottom --}}
@if ($s = $show('performance'))
@php($p = $s->parts())
<section class="v-over bottom" id="{{ $s->slug }}">
  <div class="v-bg">@include('member._bg')</div>
  <div class="v-copy">
    <h2><span>{{ $p['headline'] ?? $s->title }}</span></h2>
    @foreach ($p['paras'] as $para)<p>{{ $para }}</p>@endforeach
    @include('member._stats', ['facts' => $p['facts']])
    @include('member._video')
  </div>
</section>
@endif

{{-- 5. Picture, then the text under it --}}
@foreach (['safety', 'training'] as $slug)
@if ($s = $show($slug))
@php($p = $s->parts())
<section class="v-stack" id="{{ $s->slug }}">
  <div class="v-bg">@include('member._bg')</div>
  <div class="v-copy">
    <h2><span>{{ $p['headline'] ?? $s->title }}</span></h2>
    @foreach ($p['paras'] as $para)<p>{{ $para }}</p>@endforeach
    @include('member._stats', ['facts' => $p['facts']])
    @include('member._video')
  </div>
</section>
@endif
@endforeach

{{-- 6. Centered header, then info cards --}}
@if ($s = $show('delivery'))
@php($p = $s->parts())
<section class="v-info" id="{{ $s->slug }}">
  <h1>{{ $s->title }}</h1>
  <p class="v-tag">{{ $p['headline'] ?? $s->summary }}</p>
  @foreach ($p['paras'] as $para)<p class="v-lede">{{ $para }}</p>@endforeach
  <div class="v-stats3-c">@include('member._stats', ['facts' => $p['facts']])</div>
  @include('member._video')
  @if ($sp = $show('specs'))
  @php($sparts = $sp->parts())
  <p class="v-label" id="specs">{{ $sp->title }}</p>
  @foreach ($sparts['paras'] as $para)<p class="v-lede">{{ $para }}</p>@endforeach
  @if ($hero && ($hf = $hero->parts()['facts']))<dl class="v-headnums">@foreach (array_slice($hf, 0, 4) as [$k, $v])<div><dd>{{ $v }}</dd><dt>{{ $k }}</dt></div>@endforeach</dl>@endif
  <div class="v-infocards" id="specs-row">
    @foreach ($sparts['groups'] as [$name, $rows])
      <div class="v-infocard"><h2>{{ $name }}</h2><dl>@foreach ($rows as [$k, $v])<div><dt>{{ $k }}</dt><dd>{{ $v }}</dd></div>@endforeach</dl></div>
    @endforeach
  </div>
  <div class="v-sdots" aria-hidden="true">@foreach ($sparts['groups'] as $g)<i></i>@endforeach</div>
  @endif
</section>
@endif

{{-- 7. Video --}}
@if ($hero)
<section class="v-watch">
  @php($s = $hero)
  @if ($hero->hasVideo())@include('member._video')@else<div class="v-video v-vph" role="img" aria-label="Video placeholder"><span>Video placeholder</span></div>@endif
  <h2><span>See it for yourself</span></h2>
  <p>Watch {{ $hero->title }} in flight.</p>
</section>
@endif

{{-- 8. End --}}
<section class="v-end">
  @if ($hero)<div class="v-bg">@php($s = $hero)@include('member._bg')</div>@endif
  <div class="v-end-in">
    @include('member._vname', ['tag' => 'p', 'class' => 'v-name', 'text' => $hero?->title])
    <a class="btn solid" href="{{ route('dashboard') }}">Reserve now</a>
  </div>
  <p class="v-note">Specifications are targets for the pioneer edition and may change before delivery. Where and how you may fly depends on the aviation rules of your country.</p>
</section>
<script>(function(){var t=document.querySelector('.v-track');if(!t)return;var dots=document.querySelectorAll('.v-ctrl .dots i'),cards=t.children;function idx(){var best=0,d=1e9;[].forEach.call(cards,function(c,i){var x=Math.abs(c.offsetLeft-t.scrollLeft-parseFloat(getComputedStyle(t).paddingLeft));if(x<d){d=x;best=i}});return best}function upd(){var i=idx();dots.forEach(function(e,k){e.className=k===i?'on':''});document.querySelector('.v-ctrl .prev').disabled=i===0;document.querySelector('.v-ctrl .next').disabled=i===cards.length-1}function go(i){i=Math.max(0,Math.min(cards.length-1,i));t.scrollTo({left:cards[i].offsetLeft-parseFloat(getComputedStyle(t).paddingLeft),behavior:'smooth'})}document.querySelector('.v-ctrl .prev').addEventListener('click',function(){go(idx()-1)});document.querySelector('.v-ctrl .next').addEventListener('click',function(){go(idx()+1)});t.addEventListener('scroll',function(){clearTimeout(t._w);t._w=setTimeout(upd,60)},{passive:true});t.addEventListener('keydown',function(e){if(e.key==='ArrowRight'){go(idx()+1);e.preventDefault()}if(e.key==='ArrowLeft'){go(idx()-1);e.preventDefault()}});upd()})();</script>
<script>(function(){var r=document.getElementById('specs-row');if(!r)return;var d=document.querySelectorAll('.v-sdots i');function u(){var c=r.children[0];if(!c)return;var i=Math.round(r.scrollLeft/(c.offsetWidth+8));d.forEach(function(e,k){e.className=k===i?'on':''})}r.addEventListener('scroll',u,{passive:true});u()})();</script>
@endsection
