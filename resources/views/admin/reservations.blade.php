@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page">@include('admin._nav')
  <h1>Reservations</h1>
  <form method="get" class="inline-form"><input name="q" class="field" placeholder="Search reference, name or member number" value="{{ request('q') }}"><button class="btn ghost" type="submit">Search</button></form>
  <table class="tbl">
    <thead><tr><th>Reference</th><th>Member</th><th>Status</th><th class="r">Deposit</th><th>Agreed</th></tr></thead>
    <tbody>
    @forelse ($rows as $r)
      <tr><td class="mono">{{ $r->reference }}</td><td>{{ $r->user->name }} <span class="hint mono">{{ $r->user->member_number }}</span></td><td><span class="tag">{{ $r->statusLabel() }}</span></td>
        <td class="r">@money($r->deposit_cents)</td><td>{{ $r->agreed_at->timezone('America/New_York')->format('M j, Y g:i a') }}</td></tr>
    @empty
      <tr><td colspan="5" class="hint">No reservations yet.</td></tr>
    @endforelse
    </tbody>
  </table>
  {{ $rows->links() }}
</div>
@endsection
