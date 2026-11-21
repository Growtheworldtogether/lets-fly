@php $db = $d['db'] ?? []; $drv = old('driver', $db['driver'] ?? 'sqlite'); @endphp
<h1>Database</h1>
<p class="lead">Where member accounts, reservations and payments are kept. The connection is tested before you move on.</p>
<label class="choice"><input type="radio" name="driver" value="sqlite" @checked($drv === 'sqlite')><span><b>SQLite file</b>The simplest choice. One file inside the app folder. Good for a single server with modest traffic.</span></label>
<label class="choice"><input type="radio" name="driver" value="mysql" @checked($drv === 'mysql')><span><b>MySQL or MariaDB</b>The usual choice for a live site. Create an empty database and a user for it first.</span></label>
<div class="grid" id="mysql" style="margin-top:1rem">
    <label>Host<input name="host" value="{{ old('host', $db['host'] ?? '127.0.0.1') }}">@error('host')<span class="err">{{ $message }}</span>@enderror</label>
    <label>Port<input name="port" value="{{ old('port', $db['port'] ?? '3306') }}" inputmode="numeric">@error('port')<span class="err">{{ $message }}</span>@enderror</label>
    <label>Database name<input name="name" value="{{ old('name', $db['name'] ?? '') }}">@error('name')<span class="err">{{ $message }}</span>@enderror</label>
    <label>User<input name="user" value="{{ old('user', $db['user'] ?? '') }}">@error('user')<span class="err">{{ $message }}</span>@enderror</label>
    <label class="wide">Password<input type="password" name="pass" autocomplete="new-password"></label>
</div>
@error('driver')<span class="err">{{ $message }}</span>@enderror
<div class="actions"><button class="btn">Test and continue</button><a class="btn ghost" href="{{ route('setup', 'site') }}">Back</a></div>
<script>
(function () {
    var box = document.getElementById('mysql');
    function sync() { var on = document.querySelector('input[name=driver]:checked').value === 'mysql'; box.style.display = on ? '' : 'none'; box.querySelectorAll('input').forEach(function (i) { i.disabled = !on; }); }
    document.querySelectorAll('input[name=driver]').forEach(function (r) { r.addEventListener('change', sync); }); sync();
})();
</script>
