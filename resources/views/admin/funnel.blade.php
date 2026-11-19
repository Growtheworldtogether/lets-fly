@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page">@include('admin._nav')
  <h1>Funnel</h1>
  <p class="lead">How many invites reached each step.</p>
  <div class="funnel">
    @foreach ($rows as $r)
      <div class="frow"><span>{{ $r['label'] }}</span><span class="bar-track"><span class="bar-fill" style="width: {{ $r['pct'] }}%"></span></span><span class="mono">{{ $r['n'] }}</span></div>
    @endforeach
  </div>
</div>
@endsection
