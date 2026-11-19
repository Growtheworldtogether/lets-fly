@extends('layouts.app')
@section('content')
<section class="gate">
  <p class="eyebrow">Invitation only</p>
  <h1>Enter your invite code</h1>
  <form method="post" action="{{ route('gate.enter') }}" autocomplete="off">@csrf
    <label class="sr" for="code">Invite code</label>
    <input id="code" name="code" class="field mono" placeholder="XXXX-XXXX-XXXX-XXXX" required autofocus maxlength="40" spellcheck="false" autocapitalize="characters">
    <button class="btn solid" type="submit">Continue</button>
  </form>
</section>
@endsection
