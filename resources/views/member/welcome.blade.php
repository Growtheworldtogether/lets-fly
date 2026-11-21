@extends('layouts.app')
@section('title', 'Welcome · '.brand('name'))
@section('bodyclass', 'studio welcome-page')
@section('nomenu', '1')
@section('textsize', 'portal')
@section('backdrop', route('vehicle.art', 'training-default.webp'))
@section('content')
<section class="w-page">
  <p class="eyebrow">Welcome{{ $user->member_number ? ' · '.$user->member_number : '' }}</p>
  @include('member._vname', ['tag' => 'h1', 'class' => 'v-name', 'text' => brand('vehicle')])
  <p class="w-hello">{{ $user->name }}, you are in.</p>
  <p class="lead">Your invitation gives you early access to the {{ brand('vehicle') }}. Next you can see everything about it: the design, how it flies, the cockpit, the performance, safety, training and every specification.</p>
  <p class="w-why"><b>Why you?</b> We kept this list small and chose each person by hand. You came to mind because we wanted the first people to see it to be people who would love it as much as we do. Thank you for being one of them.</p>
  <ol class="w-steps">
    <li><b>See the vehicle</b><span>Every detail, section by section.</span></li>
    <li><b>Reserve yours</b><span>Review the agreement and wire your deposit to escrow.</span></li>
    <li><b>Pay the balance</b><span>Due {{ \App\Services\Pricing::balanceDays() }} days after your allocation milestone is confirmed.</span></li>
  </ol>
  <form method="post" action="{{ route('welcome.done') }}">@csrf
    <button class="btn solid" type="submit">View the vehicle</button>
  </form>
</section>
@endsection
