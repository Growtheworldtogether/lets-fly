@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page">@include('admin._nav')
  <h1>{{ count($items) }} {{ count($items) === 1 ? 'invite' : 'invites' }} created</h1>
  <div class="panel code-panel" role="status">
    <p class="eyebrow">Copy or download these codes now.</p>
    <p class="hint">The site keeps only a scrambled version of each code, so it cannot show them again, only replace one ("New code"). This page stays available for 15 minutes or until you press Done. Anyone who has a code can sign in, so delete the file after you have sent the codes out.</p>
    <div class="inline-form">
      <button class="btn ghost" type="button" id="copyall">Copy all</button>
      <a class="btn ghost" href="{{ route('admin.invites.bulk.csv') }}">Download CSV</a>
      <form method="post" action="{{ route('admin.invites.bulk.clear') }}">@csrf<button class="btn ghost" type="submit">Done, hide the codes</button></form>
      <span class="hint" id="copied" role="status"></span>
    </div>
  </div>
  <table class="tbl" id="codes">
    <thead><tr><th>Name</th><th>Email</th><th>Invite code</th></tr></thead>
    <tbody>
    @foreach ($items as $i)
      <tr><td>{{ $i['name'] }}</td><td>{{ $i['email'] ?: '—' }}</td><td class="mono">{{ $i['code'] }}</td></tr>
    @endforeach
    </tbody>
  </table>
</div>
<script>
document.getElementById('copyall').addEventListener('click', function () {
  var text = Array.prototype.map.call(document.querySelectorAll('#codes tbody tr'), function (tr) {
    var c = tr.children; return c[0].textContent.trim() + '\t' + c[2].textContent.trim();
  }).join('\n');
  var done = function () { document.getElementById('copied').textContent = 'Copied. Paste it into your notes or spreadsheet.'; };
  if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).then(done); }
  else { var t = document.createElement('textarea'); t.value = text; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); done(); }
});
</script>
@endsection
