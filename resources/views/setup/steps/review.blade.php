@php $mail = $d['mail']; $db = $d['db']; @endphp
<h1>Ready to install</h1>
<p class="lead">Check your answers. Installing creates the tables and the administrator's invite code, and takes up to a minute. Do not close the page.</p>
<ul class="rows">
    <li><span>Web address</span><strong>{{ $d['url'] }}</strong></li>
    <li><span>Time zone</span><strong>{{ $d['tz'] }}</strong></li>
    <li><span>Database</span><strong>{{ $db['driver'] === 'sqlite' ? 'SQLite file' : $db['name'].' on '.$db['host'] }}</strong></li>
    <li><span>Email</span><strong>{{ $mail['mode'] === 'smtp' ? $mail['user'].' via '.$mail['host'] : 'Later (written to a log)' }}</strong></li>
    <li><span>Email sender name</span><strong>{{ $mail['sender'] }}</strong></li>
    <li><span>Updates from</span><strong>{{ $d['updates']['repo'] }} &middot; {{ $d['updates']['token'] !== '' ? 'token saved' : 'no token (public repository)' }}</strong></li>
    <li><span>Administrator</span><strong>{{ $d['admin']['name'] }} &middot; {{ $d['admin']['email'] }}</strong></li>
</ul>
<label class="choice" style="margin-top:1.25rem"><input type="checkbox" name="live" value="1" @checked(str_starts_with($d['url'], 'https://'))><span><b>This is a live site</b>Turns off debug pages and detailed errors. Leave unticked on your own computer.</span></label>
<div class="actions"><button class="btn">Install now</button><a class="btn ghost" href="{{ route('setup', 'admin') }}">Back</a></div>
