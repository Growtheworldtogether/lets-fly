@extends('layouts.app')
@section('content')
<section class="gate">
  <p class="eyebrow">One more step</p>
  <h1>Enter the code we emailed you</h1>
  <form method="post" action="{{ route('signin.code') }}">@csrf
    <label class="sr" for="code">6-digit code</label>
    <input id="code" name="code" class="field mono" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code">
    <button class="btn solid" type="submit">Sign in</button>
  </form>
  <form method="post" action="{{ route('signin.resend') }}">@csrf<button class="link" type="submit">Send a new code</button></form>
</section>
@endsection
