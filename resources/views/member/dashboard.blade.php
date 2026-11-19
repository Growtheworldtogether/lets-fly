@extends('layouts.app')
@section('content')
<section class="gate"><p class="eyebrow">Member {{ auth()->user()->member_number }}</p><h1>Welcome, {{ auth()->user()->name }}</h1></section>
@endsection
