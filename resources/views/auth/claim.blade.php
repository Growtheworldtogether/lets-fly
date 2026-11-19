@extends('layouts.app')
@section('bodyclass', 'studio')
@section('content')
<section class="gate">
  <p class="eyebrow">Welcome</p>
  <h1>{{ $invite->name }}</h1>
  <p class="lead">Set a password to finish creating your account.</p>
  <form method="post" action="{{ route('signin.claim') }}">@csrf
    @unless ($invite->email)
      <label for="email">Email</label><input id="email" name="email" type="email" class="field" required value="{{ old('email') }}" autocomplete="email">
    @endunless
    <label for="password">Password</label><input id="password" name="password" type="password" class="field" required minlength="10" autocomplete="new-password">
    <label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" class="field" required autocomplete="new-password">
    <p class="hint">At least 10 characters, with letters and numbers.</p>
    <button class="btn solid" type="submit">Create account</button>
  </form>
</section>
@endsection
