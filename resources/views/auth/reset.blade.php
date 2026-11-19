@extends('layouts.app')
@section('content')
<section class="gate">
  <p class="eyebrow">Reset password</p>
  <h1>{{ $user->name }}</h1>
  @if (! $sent)
    <p class="lead">We'll email a 6-digit code to the address on your account.</p>
    <form method="post" action="{{ route('reset.send') }}">@csrf<button class="btn solid" type="submit">Email me a code</button></form>
  @else
    <form method="post" action="{{ route('reset.update') }}">@csrf
      <label for="code">6-digit code</label><input id="code" name="code" class="field mono" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code">
      <label for="password">New password</label><input id="password" name="password" type="password" class="field" required minlength="10" autocomplete="new-password">
      <label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" class="field" required autocomplete="new-password">
      <p class="hint">At least 10 characters, with letters and numbers.</p>
      <button class="btn solid" type="submit">Change password</button>
    </form>
    <form method="post" action="{{ route('reset.send') }}">@csrf<button class="link" type="submit">Send a new code</button></form>
  @endif
  <p><a class="link" href="{{ route('signin') }}">Back to sign in</a></p>
</section>
@endsection
