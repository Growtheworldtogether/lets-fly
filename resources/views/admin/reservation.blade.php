@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
@php($money = fn ($c) => \App\Support\Money::cents((int) $c)->format())
<div class="page">@include('admin._nav')
  <p class="eyebrow"><a href="{{ route('admin.reservations') }}">Reservations</a></p>
  <h1>{{ $r->reference }}</h1>
  <div class="row"><div><strong>{{ $r->user->name }}</strong> <span class="mono hint">{{ $r->user->member_number }}</span><br><span class="hint">{{ $r->user->email }}</span></div><span class="tag">{{ $r->statusLabel() }}</span></div>
  <p class="hint">Agreement v{{ $r->agreement_version }} accepted {{ $r->agreed_at->timezone('America/New_York')->format('M j, Y g:i a') }} ET from {{ $r->agreed_ip }}.
    @if ($r->deposit_verified_at) Deposit received in escrow {{ $r->deposit_verified_at->timezone('America/New_York')->format('M j, Y g:i a') }}. @endif
    @if ($r->milestone_confirmed_at) Milestone confirmed {{ $r->milestone_confirmed_at->timezone('America/New_York')->format('M j, Y g:i a') }}@if ($r->milestoneConfirmer) by {{ $r->milestoneConfirmer->name }}@endif. @endif
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
              <form method="post" action="{{ route('admin.payment.verify', $p) }}" onsubmit="return confirm('Confirm this wire arrived in the escrow account, on behalf of {{ addslashes(\App\Services\Escrow::agent()) }}?')">@csrf<button class="btn solid" type="submit">Confirm received in escrow</button></form>
              <form method="post" action="{{ route('admin.payment.reject', $p) }}" class="inline-form">@csrf<input name="note" class="field" placeholder="What is wrong with it" required maxlength="200"><button class="btn ghost" type="submit">Reject</button></form>
            </div>
          @endif
        </div>
      @endforeach
    </div>
  @endforeach

  @php($held = $r->heldCents())
  <section class="escrow-panel" id="escrow">
    <h2>Escrow</h2>
    <p class="hint">Receipts, the milestone and releases are confirmed here on behalf of {{ \App\Services\Escrow::agent() }}. Held now: <strong>{{ $money($held) }}</strong> · {{ $r->escrowLabel() }}.</p>
    @if ($r->escrowEntries->isNotEmpty())
      <table class="tbl ledger"><thead><tr><th>When</th><th>Event</th><th class="r">Amount</th><th>By</th><th>Note</th></tr></thead><tbody>
        @foreach ($r->escrowEntries as $e)
          <tr><td>{{ $e->created_at->timezone('America/New_York')->format('M j, Y g:i a') }}</td><td>{{ ucfirst($e->event) }}</td><td class="r">{{ $money($e->amount_cents) }}</td><td>{{ $e->user?->name }}</td><td>{{ $e->note }}</td></tr>
        @endforeach
      </tbody></table>
    @endif

    @unless ($r->cancelled_at)
    <div class="acts">
      @if ($r->status === 'in_escrow')
        <form method="post" action="{{ route('admin.reservation.milestone', $r) }}" class="stack-form" onsubmit="return confirm('Confirm the allocation milestone? The balance invoice is issued and emailed now, due in {{ \App\Services\Pricing::balanceDays() }} days.')">@csrf
          <label for="m-note">Confirm the allocation milestone (optional note)</label><input id="m-note" name="note" class="field" maxlength="200">
          <p class="hint">This issues the {{ $money($r->balance_cents) }} balance invoice, emails it, and starts the {{ \App\Services\Pricing::balanceDays() }}-day clock.</p>
          <button class="btn solid" type="submit">Confirm milestone and invoice balance</button>
        </form>
      @endif

      @if ($r->balance_due_at && ! $r->balance_verified_at)
        <form method="post" action="{{ route('admin.reservation.extend', $r) }}" class="stack-form">@csrf
          <label for="due">Wait longer: move the balance due date to</label><input id="due" name="due" type="date" class="field" required min="{{ now()->addDay()->format('Y-m-d') }}">
          <button class="btn ghost" type="submit">Push back the deadline</button>
        </form>
      @endif

      @if ($r->milestone_confirmed_at && $held > 0)
        <form method="post" action="{{ route('admin.reservation.release', $r) }}" class="stack-form" onsubmit="return confirm('Mark {{ $money($held) }} as released to {{ addslashes(brand('name')) }}? Record this only after the escrow agent has released it.')">@csrf
          <label for="rel-note">Release funds to {{ brand('name') }} (settlement reference)</label><input id="rel-note" name="note" class="field" required maxlength="200">
          <button class="btn ghost" type="submit">Mark {{ $money($held) }} as released</button>
        </form>
      @endif

      <form method="post" action="{{ route('admin.reservation.update', $r) }}" class="stack-form">@csrf
        <label for="upd">Send the member an update (a delay, a new date, anything they should know)</label><textarea id="upd" name="message" class="field area" required maxlength="1000"></textarea>
        <button class="btn ghost" type="submit">Email update</button>
      </form>

      @if ($r->status !== 'completed' || $held > 0)
        <form method="post" action="{{ route('admin.reservation.cancel', $r) }}" class="stack-form" onsubmit="return confirm('Cancel this reservation? Open invoices will be voided and this cannot be undone.')">@csrf
          <label for="outcome">If this reservation will not go ahead</label>
          <select id="outcome" name="outcome" class="field">
            @if ($held > 0)
              <option value="refund">Cancel and refund {{ $money($held) }} from escrow to the member</option>
              <option value="release">Cancel and release {{ $money($held) }} to {{ brand('name') }} (as the agreement allows)</option>
            @else
              <option value="none">Cancel (no funds were received)</option>
            @endif
          </select>
          <label for="c-note">Note for the record {{ $held > 0 ? '(required)' : '(optional)' }}</label><input id="c-note" name="note" class="field" maxlength="200" @required($held > 0)>
          <button class="btn ghost" type="submit">Cancel reservation</button>
        </form>
      @endif
    </div>
    @endunless
  </section>
</div>
@endsection
