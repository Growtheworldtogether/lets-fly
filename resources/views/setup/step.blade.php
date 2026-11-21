@extends('setup.layout')
@section('body')
<ol class="steps" aria-label="Setup steps">
    @foreach ($steps as $id => $label)
        <li class="{{ $id === $step ? 'on' : (! empty($done[$id]) ? 'ok' : '') }}">{{ $label }}</li>
    @endforeach
</ol>
<main class="card">
    @if ($errors->has('install') || $errors->has('setup'))
        <div class="alert" role="alert">{{ $errors->first('install') ?: $errors->first('setup') }}</div>
    @endif
    <form method="post" action="{{ route('setup.save', $step) }}" autocomplete="off">
        @csrf
        @include('setup.steps.'.$step)
    </form>
</main>
@endsection
