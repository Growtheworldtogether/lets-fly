@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page">@include('admin._nav')
  <h1>Overview</h1>
  <div class="stats">
    <div class="panel"><p class="eyebrow">Invites</p><p class="big">{{ \App\Models\Invite::count() }}</p></div>
    <div class="panel"><p class="eyebrow">Members</p><p class="big">{{ \App\Models\User::where('role', 'member')->count() }}</p></div>
    <div class="panel"><p class="eyebrow">Unclaimed</p><p class="big">{{ \App\Models\Invite::where('status', 'active')->count() }}</p></div>
  </div>
</div>
@endsection
