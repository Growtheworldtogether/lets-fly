@extends('setup.layout')
@section('body')
<main class="card" style="grid-column:1/-1;max-width:640px;margin:0 auto;width:100%">
    @if ($code)
        <h1>New invite code</h1>
        <p class="lead">For {{ $email }}. It is shown once, so copy it now. Enter it on the front page, then choose a password.</p>
        <code class="keybox code">{{ $code }}</code>
    @else
        <h1>Password changed</h1>
        <p class="lead">The password for {{ $email }} is changed and any lock on the account is cleared.</p>
    @endif
    <p class="muted">Recovery is switched off again. The file was removed from the server; delete it yourself if it is still there.</p>
</main>
@endsection
