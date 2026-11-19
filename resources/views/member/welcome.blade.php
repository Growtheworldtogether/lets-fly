@extends('layouts.app')
@section('title', 'Welcome · '.brand('name'))
@section('bodyclass', 'studio')
@section('content')
<section class="w-page">
  <p class="eyebrow">Welcome{{ $user->member_number ? ' · '.$user->member_number : '' }}</p>
  <h1 class="v-name">{{ brand('vehicle') }}</h1>
  <p class="w-hello">{{ $user->name }}, you are in.</p>
  <p class="lead">Your invitation gives you early access to the {{ brand('vehicle') }}. Next you can see everything about it: the design, how it flies, the cockpit, the performance, safety, training and every specification.</p>
  <ol class="w-steps">
    <li><b>See the vehicle</b><span>Every detail, section by section.</span></li>
    <li><b>Reserve yours</b><span>Review the agreement and pay the deposit by wire.</span></li>
    <li><b>Take delivery</b><span>Your pilot training and handover, in order.</span></li>
  </ol>
  <form method="post" action="{{ route('welcome.done') }}">@csrf
    <button class="btn solid" type="submit">View the vehicle</button>
  </form>
</section>
@endsection
