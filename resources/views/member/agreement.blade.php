@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page narrow">
  <h1>Reservation agreement</h1>
  <div class="panel prose agreement">
    @foreach (preg_split('/\n\s*\n/', trim($agreement->rendered())) as $p)<p>{!! nl2br(e($p)) !!}</p>@endforeach
  </div>
  <form method="post" action="{{ route('reserve.store') }}" class="stack">@csrf
    <label class="check"><input type="checkbox" name="agree" value="1" required> I have read the agreement and I accept it.</label>
    <button class="btn solid" type="submit">Accept and reserve</button>
  </form>
</div>
@endsection
