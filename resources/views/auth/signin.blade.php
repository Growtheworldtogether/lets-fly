@extends('layouts.app')
@section('content')
<section class="gate">
  <p class="eyebrow">Welcome back</p>
  <h1>{{ $invite->name }}</h1>
  <form method="post" action="{{ route('signin.post') }}">@csrf
    <label for="password">Password</label><input id="password" name="password" type="password" class="field" required autofocus autocomplete="current-password">
    <button class="btn solid" type="submit">Sign in</button>
  </form>
  <form method="post" action="{{ route('gate.leave') }}">@csrf<button class="link" type="submit">Use a different code</button></form>
</section>
@endsection
