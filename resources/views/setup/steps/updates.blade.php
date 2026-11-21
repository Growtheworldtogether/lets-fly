@php $u = $d['updates'] ?? []; @endphp
<h1>Updates</h1>
<p class="lead">The site updates itself from the Releases page of a GitHub repository (Admin, System, Check for updates). If that repository is private, the site needs a read-only token to see it. You can leave the token empty now and add it later.</p>
<div class="grid">
    <label class="wide">GitHub repository<input name="repo" value="{{ old('repo', $u['repo'] ?? config('updates.repo')) }}" required><small>Written as owner/name.</small>@error('repo')<span class="err">{{ $message }}</span>@enderror</label>
    <label class="wide">Access token (only for a private repository)<input type="password" name="token" value="" autocomplete="new-password" placeholder="github_pat_..."><small>On GitHub: Settings, Developer settings, Personal access tokens, Fine-grained tokens. Choose this repository only, with Contents: Read-only, and set an expiry. It is saved in the .env file on this server and tested before you continue.</small>@error('token')<span class="err">{{ $message }}</span>@enderror</label>
</div>
<div class="actions"><button class="btn">Test and continue</button><a class="btn ghost" href="{{ route('setup', 'email') }}">Back</a></div>
