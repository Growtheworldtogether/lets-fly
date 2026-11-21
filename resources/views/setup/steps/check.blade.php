<h1>Server check</h1>
<p class="lead">The site needs these to run. Items marked as missing must be fixed before you can go on.</p>
<ul class="rows">
    @foreach ($rows as $r)
        <li><span>{{ $r['label'] }}<br><small class="muted">{{ $r['note'] }}</small></span><span class="{{ $r['ok'] ? 'good' : ($r['blocking'] ? 'bad' : 'warn') }}">{{ $r['ok'] ? 'Ready' : ($r['blocking'] ? 'Missing' : 'Note') }}</span></li>
    @endforeach
</ul>
@error('check')<span class="err">{{ $message }}</span>@enderror
<div class="actions">
    <button class="btn" @disabled($blocked)>Continue</button>
    <a class="btn ghost" href="{{ route('setup', 'check') }}">Check again</a>
</div>
