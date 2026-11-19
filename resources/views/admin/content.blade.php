@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page">@include('admin._nav')
  <h1>Vehicle pages</h1>
  <p class="lead">What members read while learning about the vehicle. Each section can have a picture and a video.</p>
  <table class="tbl">
    <thead><tr><th>Order</th><th>Section</th><th>Picture</th><th>Video</th><th>Shown</th><th></th></tr></thead>
    <tbody>
    @foreach ($sections as $s)
      <tr>
        <td class="mono">{{ $s->sort }}</td><td>{{ $s->title }}</td>
        <td>{{ $s->image_id ? 'Yes' : 'Placeholder' }}</td>
        <td>{{ $s->hasVideo() ? 'Yes' : '—' }}</td>
        <td>{{ $s->active ? 'Yes' : 'Hidden' }}</td>
        <td class="acts"><a class="link" href="{{ route('admin.content.edit', $s->slug) }}">Edit</a></td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endsection
