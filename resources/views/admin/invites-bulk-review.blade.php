@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page">@include('admin._nav')
  <h1>Review the list</h1>
  @if ($tooMany)<p class="hint" role="alert" style="color:#e8b04a">Your list has more than {{ \App\Services\BulkInvites::MAX }} names. Only the first {{ \App\Services\BulkInvites::MAX }} are shown. Do the rest in a second batch.</p>@endif
  <table class="tbl">
    <thead><tr><th>Name</th><th>Email</th><th>What will happen</th></tr></thead>
    <tbody>
    @foreach ($rows as $r)
      <tr>
        <td>{{ $r['name'] ?: '—' }}</td>
        <td>{{ $r['email'] ?: '—' }}</td>
        <td>@if ($r['status'] === 'ok')<span class="tag">New invite</span>
            @elseif ($r['status'] === 'known')<span class="tag">{{ $r['create'] ? 'Will be created again' : 'Skipped' }}</span> <span class="hint">{{ $r['note'] }}</span>
            @else<span class="tag">Skipped</span> <span class="hint">{{ $r['note'] }}</span>@endif</td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <div class="inline-form">
    @if ($create > 0)
    <form method="post" action="{{ route('admin.invites.bulk.store') }}">@csrf
      <textarea name="names" hidden>{{ $names }}</textarea><input type="hidden" name="label" value="{{ $label }}"><input type="hidden" name="include_known" value="{{ $include_known ? 1 : 0 }}">
      <button class="btn solid" type="submit">Create {{ $create }} {{ $create === 1 ? 'invite' : 'invites' }}</button>
    </form>
    @endif
    @if (collect($rows)->contains('status', 'known') && ! $include_known)
    <form method="post" action="{{ route('admin.invites.bulk.review') }}">@csrf
      <textarea name="names" hidden>{{ $names }}</textarea><input type="hidden" name="label" value="{{ $label }}"><input type="hidden" name="include_known" value="1">
      <button class="btn ghost" type="submit">Create the repeated names too</button>
    </form>
    @endif
    <a class="btn ghost" href="{{ route('admin.invites') }}">Back and edit</a>
  </div>
  @if ($create === 0)<p class="hint">@if (collect($rows)->contains('status', 'known') && ! $include_known)Nothing will be created as the list stands. Choose "Create the repeated names too", or go back and edit it.@else Nothing can be created from this list. Go back and fix it.@endif</p>@endif
  <p class="hint">Nothing has been created yet. The codes appear on the next page, once.</p>
</div>
@endsection
