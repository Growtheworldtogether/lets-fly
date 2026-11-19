@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page">@include('admin._nav')
  <h1>Agreement</h1>
  <p class="lead">Version {{ $current->version }} is live. Publishing saves a new version; members who already accepted keep the version they accepted.</p>
  <form method="post" action="{{ route('admin.agreement.publish') }}" class="stack wide-stack">@csrf
    <label for="body">Agreement text</label><textarea id="body" name="body" class="field area huge" required>{{ old('body', $current->body) }}</textarea>
    <p class="hint">Placeholders filled in for each member: {company} {vehicle} {price} {public_price} {deposit} {balance} {days}. Leave a blank line between paragraphs.</p>
    <button class="btn solid" type="submit">Publish as version {{ $current->version + 1 }}</button>
  </form>
</div>
@endsection
