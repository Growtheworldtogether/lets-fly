@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
@php($money = fn ($c) => \App\Support\Money::cents((int) $c)->format())
<div class="page narrow">
  <p class="eyebrow">Member {{ auth()->user()->member_number }}</p>
  <h1>Welcome, {{ auth()->user()->name }}</h1>

  @if (! $r)
    <div class="panel">
      <p class="eyebrow">Pioneer allocation</p>
      <p class="big">{{ $money($terms['price']) }}</p>
      <p class="lead">Public price in 2028 is projected at {{ $money($terms['public']) }}. Reserve with a {{ $money($terms['deposit']) }} deposit now and pay the {{ $money($terms['balance']) }} balance within {{ $terms['days'] }} days after we verify your deposit.</p>
      <a class="btn solid" href="{{ route('reserve') }}">Reserve my allocation</a>
    </div>
  @else
    <div class="panel">
      <div class="row"><span class="eyebrow">Reservation {{ $r->reference }}</span><span class="tag">{{ $r->statusLabel() }}</span></div>
      <table class="tbl sum">
        <tr><td>Pioneer allocation</td><td class="r">{{ $money($r->price_cents) }}</td></tr>
        <tr><td>Deposit</td><td class="r">{{ $money($r->deposit_cents) }}</td></tr>
        <tr><td>Balance — {{ $r->balance_due_at ? 'due '.$r->balance_due_at->timezone('America/New_York')->format('M j, Y') : 'due '.$terms['days'].' days after your deposit is verified' }}</td><td class="r">{{ $money($r->balance_cents) }}</td></tr>
      </table>
      <p class="hint">Agreement version {{ $r->agreement_version }} accepted {{ $r->agreed_at->timezone('America/New_York')->format('M j, Y g:i a') }} ET.</p>
    </div>

    <h2>Invoices</h2>
    <table class="tbl">
      <thead><tr><th>Number</th><th>For</th><th class="r">Amount</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @foreach ($r->invoices as $inv)
        <tr><td class="mono">{{ $inv->number }}</td><td>{{ $inv->title() }}</td><td class="r">{{ $money($inv->amount_cents) }}</td><td><span class="tag">{{ $inv->status }}</span></td>
          <td class="acts"><a class="link" href="{{ route('invoice.pdf', $inv) }}">Download PDF</a></td></tr>
      @endforeach
      </tbody>
    </table>

    <h2>How to pay</h2>
    <div class="panel">
      <p>Send the amount by wire transfer using these details. Use <strong class="mono">{{ auth()->user()->member_number }}</strong> as the payment reference so we can match it.</p>
      @if (trim($wire) !== '')
        <pre class="wire">{{ $wire }}</pre>
      @else
        <p class="hint">Wire details will appear here once they are set up.</p>
      @endif
      <p class="hint">Card payment: Payment option unavailable for now.</p>
    </div>
  @endif
</div>
@endsection
