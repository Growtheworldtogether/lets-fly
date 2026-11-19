@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page">@include('admin._nav')
  <h1>Members</h1>
  <form method="get" class="inline-form"><input name="q" class="field" placeholder="Search name, email or member number" value="{{ request('q') }}"><button class="btn ghost" type="submit">Search</button></form>
  <table class="tbl">
    <thead><tr><th>Number</th><th>Name</th><th>Email</th><th>Last sign-in</th><th></th></tr></thead>
    <tbody>
    @forelse ($members as $m)
      <tr>
        <td class="mono">{{ $m->member_number }}</td><td>{{ $m->name }}</td><td>{{ $m->email }}</td>
        <td>{{ $m->last_login_at?->timezone('America/New_York')->format('M j, Y g:i a') ?? '—' }}</td>
        <td class="acts">@can('impersonate')<form method="post" action="{{ route('admin.impersonate', $m) }}">@csrf<button class="link" type="submit">Log in as member</button></form>@endcan</td>
      </tr>
    @empty
      <tr><td colspan="5" class="hint">No members yet.</td></tr>
    @endforelse
    </tbody>
  </table>
  {{ $members->links() }}
</div>
@endsection
