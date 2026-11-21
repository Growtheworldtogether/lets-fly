@extends('setup.layout')
@section('body')
<main class="card" style="grid-column:1/-1;max-width:640px;margin:0 auto;width:100%">
    <h1>Administrator recovery</h1>
    <p class="lead">Use this if the administrator's invite code or password is lost. Anyone who can add a file to the server can open this page, so it switches itself off after one use, after five wrong tries, or after an hour.</p>
    <form method="post" action="{{ route('recover.save') }}" autocomplete="off">
        @csrf
        <div class="grid">
            <label class="wide">The text in the file<input type="password" name="secret" required autocomplete="off"><small>The 12 or more characters you typed into <code>{{ $file }}</code>.</small>@error('secret')<span class="err">{{ $message }}</span>@enderror</label>
            <label class="wide">What do you need?</label>
            <label class="choice wide"><input type="radio" name="action" value="invite" @checked(old('action', 'invite') === 'invite')><span><b>A new invite code</b>For an administrator who never signed in, or whose code was lost. The old code stops working.</span></label>
            <label class="choice wide"><input type="radio" name="action" value="password" @checked(old('action') === 'password')><span><b>A new password</b>For an administrator who has signed in before and forgot the password.</span></label>
            <label>Administrator's email<input type="email" name="email" value="{{ old('email') }}" required>@error('email')<span class="err">{{ $message }}</span>@enderror</label>
            <label id="nm">Name (new invite only)<input name="name" value="{{ old('name') }}"></label>
            <label class="wide" id="pw">New password<input type="password" name="password" autocomplete="new-password"><small>At least 10 characters, with letters and numbers.</small>@error('password')<span class="err">{{ $message }}</span>@enderror</label>
        </div>
        <div class="actions"><button class="btn">Continue</button></div>
    </form>
</main>
<script>
(function () {
    function sync() { var pw = document.querySelector('input[name=action]:checked').value === 'password'; document.getElementById('pw').style.display = pw ? '' : 'none'; document.getElementById('nm').style.display = pw ? 'none' : ''; }
    document.querySelectorAll('input[name=action]').forEach(function (r) { r.addEventListener('change', sync); }); sync();
})();
</script>
@endsection
