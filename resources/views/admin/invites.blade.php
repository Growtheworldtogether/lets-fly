@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page">@include('admin._nav')
  <h1>Invites</h1>
  @if (session('new_code'))
    <div class="panel code-panel" role="status">
      <p class="eyebrow">Code for {{ session('new_code.name') }}. It is shown once.</p>
      <p class="big mono">{{ session('new_code.code') }}</p>
      <p class="hint">Copy it now and send it to them yourself. It can't be shown again, only replaced.</p>
    </div>
  @endif
  <form method="post" action="{{ route('admin.invites.store') }}" class="inline-form panel">@csrf
    <div><label for="name">Name</label><input id="name" name="name" class="field" required maxlength="120" value="{{ old('name') }}"></div>
    <div><label for="email">Email (optional)</label><input id="email" name="email" type="email" class="field" value="{{ old('email') }}"></div>
    <div><label for="label">Note (optional)</label><input id="label" name="label" class="field" maxlength="120" value="{{ old('label') }}"></div>
    <div><label for="kind">Type</label><select id="kind" name="kind" class="field"><option value="member">Member</option>@can('admin.manage')<option value="admin">Admin</option>@endcan</select></div>
    <button class="btn solid" type="submit">Create invite</button>
  </form>
  <details class="panel" @if ($errors->has('names')) open @endif>
    <summary>Add many people at once</summary>
    <form method="post" action="{{ route('admin.invites.bulk.review') }}" class="stack">@csrf
      <div><label for="names">One name per line</label>
        <textarea id="names" name="names" class="field" rows="8" required maxlength="30000" placeholder="Jane Doe&#10;John Smith, john@example.com">{{ old('names') }}</textarea>
        @error('names')<p class="hint" role="alert" style="color:#e8b04a">{{ $message }}</p>@enderror
        <p class="hint">Up to {{ \App\Services\BulkInvites::MAX }} people. To add an email, put it after a comma, like "John Smith, john@example.com". These are member invites.</p></div>
      <div><label for="bulk-label">Note for this batch (optional)</label><input id="bulk-label" name="label" class="field" maxlength="120" value="{{ old('label') }}" placeholder="Example: November launch"></div>
      <button class="btn solid" type="submit">Review</button>
    </form>
  </details>
  <form method="get" class="inline-form"><input name="q" class="field" placeholder="Search name, email or note" value="{{ request('q') }}"><button class="btn ghost" type="submit">Search</button><a class="btn ghost" href="{{ route('admin.invites.export') }}">Export CSV</a></form>
  <table class="tbl">
    <thead><tr><th>Name</th><th>Email</th><th>Type</th><th>Status</th><th>Code ends</th><th></th></tr></thead>
    <tbody>
    @forelse ($invites as $i)
      <tr>
        <td>{{ $i->name }}@if ($i->label)<br><span class="hint">{{ $i->label }}</span>@endif</td>
        <td>{{ $i->email ?: ($i->user?->email ?? '—') }}</td>
        <td>{{ ucfirst($i->kind) }}</td>
        <td><span class="tag">{{ $i->status }}</span></td>
        <td class="mono">{{ $i->code_hint }}</td>
        <td class="acts">
          @if ($i->status !== 'revoked')
            <form method="post" action="{{ route('admin.invites.regenerate', $i) }}">@csrf<button class="link" type="submit">New code</button></form>
            <form method="post" action="{{ route('admin.invites.revoke', $i) }}" onsubmit="return confirm('Revoke this invite? Its code will stop working.')">@csrf<button class="link" type="submit">Revoke</button></form>
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="6" class="hint">No invites yet.</td></tr>
    @endforelse
    </tbody>
  </table>
  {{ $invites->links() }}
</div>
@endsection
