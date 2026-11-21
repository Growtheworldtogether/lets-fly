@extends('setup.layout')
@section('body')
<main class="card" style="grid-column:1/-1;max-width:640px;margin:0 auto;width:100%">
    <h1>Setup is done</h1>
    <p class="lead">This is the administrator's invite code for {{ $email }}. It is shown once and this page cannot be opened again, so copy it now.</p>
    <code class="keybox code">{{ $code }}</code>
    <p class="muted">To sign in, open the front page, enter this code, then choose a password. If the code is lost, see "Lost admin code or password" in docs/DEPLOY.md: it needs only the host's file manager, no command line.</p>
    <div class="actions"><a class="btn" href="{{ $url }}">Go to the front page</a></div>
</main>
@endsection
