@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
@php($money = fn ($c) => \App\Support\Money::cents((int) $c)->format())
<div class="page">@include('admin._nav')
  <p class="eyebrow"><a href="{{ route('admin.reservations') }}">Reservations</a></p>
  <h1>{{ $r->reference }}</h1>
  <div class="row"><div><strong>{{ $r->user->name }}</strong> <span class="mono hint">{{ $r->user->member_number }}</span><br><span class="hint">{{ $r->user->email }}</span></div><span class="tag">{{ $r->statusLabel() }}</span></div>
  <p class="hint">Agreement v{{ $r->agreement_version }} accepted {{ $r->agreed_at->timezone('America/New_York')->format('M j, Y g:i a') }} ET from {{ $r->agreed_ip }}.
    @if ($r->deposit_verified_at) Deposit verified {{ $r->deposit_verified_at->timezone('America/New_York')->format('M j, Y g:i a') }}. @endif
    @if ($r->balance_due_at) Balance due {{ $r->balance_due_at->timezone('America/New_York')->format('M j, Y') }}@if ($r->balance_due_at->isPast() && ! $r->balance_verified_at) (overdue)@endif. @endif</p>

  @foreach ($r->invoices as $inv)
    <div class="panel inv">
      <div class="row"><div><span class="mono">{{ $inv->number }}</span> <span class="hint">{{ $inv->title() }}</span></div><div><strong>{{ $money($inv->amount_cents) }}</strong> <span class="tag">{{ $inv->status }}</span></div></div>
      <p class="hint">Emailed: {{ $inv->emailed_at ? $inv->emailed_at->timezone('America/New_York')->format('M j, g:i a') : 'not sent' }}</p>
      @if ($inv->status === 'open')<form method="post" action="{{ route('admin.invoice.resend', $inv) }}">@csrf<button class="link" type="submit">Resend invoice email</button></form>@endif
      @foreach ($inv->payments as $p)
        <div class="pay">
          <span class="tag">{{ $p->status }}</span> {{ $money($p->amount_cents) }} · submitted {{ $p->submitted_at?->timezone('America/New_York')->format('M j, g:i a') }} ET
          @if ($p->proof_path) · <a class="link" href="{{ route('payment.proof', $p) }}" target="_blank" rel="noopener">View proof</a>@endif
          @if ($p->note)<br><span class="hint">Note: {{ $p->note }}</span>@endif
          @if ($p->status === 'pending')
            <div class="inline-form">
              <form method="post" action="{{ route('admin.payment.verify', $p) }}" onsubmit="return confirm('Mark this payment as received?')">@csrf<button class="btn solid" type="submit">Verify payment</button></form>
              <form method="post" action="{{ route('admin.payment.reject', $p) }}" class="inline-form">@csrf<input name="note" class="field" placeholder="What is wrong with it" required maxlength="200"><button class="btn ghost" type="submit">Reject</button></form>
            </div>
          @endif
        </div>
      @endforeach
    </div>
  @endforeach

  @if ($r->balance_due_at && ! $r->balance_verified_at && ! $r->cancelled_at)
    <form method="post" action="{{ route('admin.reservation.extend', $r) }}" class="inline-form panel">@csrf
      <div><label for="due">Move the balance due date to</label><input id="due" name="due" type="date" class="field" required min="{{ now()->addDay()->format('Y-m-d') }}"></div>
      <button class="btn ghost" type="submit">Extend</button>
    </form>
  @endif
  @if (! $r->cancelled_at && $r->status !== 'completed')
    <form method="post" action="{{ route('admin.reservation.cancel', $r) }}" onsubmit="return confirm('Cancel this reservation? Open invoices will be voided.')">@csrf<button class="link" type="submit">Cancel reservation</button></form>
  @endif
</div>
@endsection
