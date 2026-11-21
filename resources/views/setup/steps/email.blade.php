@php $m = $d['mail'] ?? []; $mode = old('mode', $m['mode'] ?? 'smtp'); @endphp
<h1>Email</h1>
<p class="lead">Invitation, payment and reminder messages are sent by email. SMTP2GO works well: copy its SMTP host, port and the user and password you made there. The sender address must be verified in SMTP2GO.</p>
<label>Sender name<input name="sender" value="{{ old('sender', $m['sender'] ?? '') }}" maxlength="80" required placeholder="e.g. Falcon Reservations"><small>The name members see in the From line of every email. You can change it later in Admin, Settings.</small>@error('sender')<span class="err">{{ $message }}</span>@enderror</label>
<div style="height:1rem"></div>
<label class="choice"><input type="radio" name="mode" value="smtp" @checked($mode === 'smtp')><span><b>Send real email now</b>Enter the mail server details below.</span></label>
<label class="choice"><input type="radio" name="mode" value="later" @checked($mode === 'later')><span><b>I will set it up later</b>Email is written to a log file instead. Members will not receive anything until you add the details to the .env file.</span></label>
<div class="grid" id="smtp" style="margin-top:1rem">
    <label>SMTP host<input name="host" value="{{ old('host', $m['host'] ?? 'mail.smtp2go.com') }}">@error('host')<span class="err">{{ $message }}</span>@enderror</label>
    <label>Port<input name="port" value="{{ old('port', $m['port'] ?? '2525') }}" inputmode="numeric">@error('port')<span class="err">{{ $message }}</span>@enderror</label>
    <label>SMTP user<input name="user" value="{{ old('user', $m['user'] ?? '') }}" autocomplete="off">@error('user')<span class="err">{{ $message }}</span>@enderror</label>
    <label>SMTP password<input type="password" name="pass" autocomplete="new-password">@error('pass')<span class="err">{{ $message }}</span>@enderror</label>
    <label class="wide">Send from address<input type="email" name="from" value="{{ old('from', $m['from'] ?? '') }}"><small>Must be a verified sender in SMTP2GO.</small>@error('from')<span class="err">{{ $message }}</span>@enderror</label>
</div>
<div class="actions"><button class="btn">Test and continue</button><a class="btn ghost" href="{{ route('setup', 'database') }}">Back</a></div>
<script>
(function () {
    var box = document.getElementById('smtp');
    function sync() { var on = document.querySelector('input[name=mode]:checked').value === 'smtp'; box.style.display = on ? '' : 'none'; box.querySelectorAll('input').forEach(function (i) { i.disabled = !on; }); }
    document.querySelectorAll('input[name=mode]').forEach(function (r) { r.addEventListener('change', sync); }); sync();
})();
</script>
