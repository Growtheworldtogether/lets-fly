<h1>First administrator</h1>
<p class="lead">This person runs the platform. No password is set here: setup ends with a one-time invite code, and they enter it at the front page and choose their own password.</p>
<div class="grid">
    <label>Full name<input name="name" value="{{ old('name', $d['admin']['name'] ?? '') }}" required>@error('name')<span class="err">{{ $message }}</span>@enderror</label>
    <label>Email<input type="email" name="email" value="{{ old('email', $d['admin']['email'] ?? '') }}" required>@error('email')<span class="err">{{ $message }}</span>@enderror</label>
</div>
<div class="actions"><button class="btn">Continue</button><a class="btn ghost" href="{{ route('setup', 'email') }}">Back</a></div>
