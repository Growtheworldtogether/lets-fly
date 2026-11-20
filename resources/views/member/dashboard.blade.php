@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
@php($money = fn ($c) => \App\Support\Money::cents((int) $c)->format())
<div class="page narrow">
  <p class="eyebrow">Member {{ auth()->user()->member_number }}</p>
  <h1>Welcome, {{ auth()->user()->name }}</h1>

  @if (! $r)
    @if ($cancelled)@include('member._escrow', ['r' => $cancelled->load('escrowEntries', 'payments')])@endif
    <div class="panel">
      <p class="eyebrow">Pioneer allocation</p>
      <p class="big">{{ $money($terms['price']) }}</p>
      <p class="lead">Public price in 2028 is projected at {{ $money($terms['public']) }}. Reserve with a {{ $money($terms['deposit']) }} deposit, held in escrow by {{ \App\Services\Escrow::agent() }} and not placed in {{ brand('name') }}'s operating funds. The {{ $money($terms['balance']) }} balance is due within {{ $terms['days'] }} days after your allocation milestone is confirmed.</p>
      @if ($escrowReady)<a class="btn solid" href="{{ route('reserve') }}">Reserve my allocation</a>@else<p class="hint">Reservations are not open yet. Please check back soon.</p>@endif
    </div>
  @else
    <div class="panel">
      <div class="row"><span class="eyebrow">Reservation {{ $r->reference }}</span><span class="tag">{{ $r->statusLabel() }}</span></div>
      <table class="tbl sum">
        <tr><td>Pioneer allocation</td><td class="r">{{ $money($r->price_cents) }}</td></tr>
        <tr><td>Deposit (held in escrow)</td><td class="r">{{ $money($r->deposit_cents) }}</td></tr>
        <tr><td>Balance — {{ $r->balance_due_at ? 'due '.$r->balance_due_at->timezone('America/New_York')->format('M j, Y') : 'due '.$terms['days'].' days after your milestone is confirmed' }}</td><td class="r">{{ $money($r->balance_cents) }}</td></tr>
      </table>
    </div>

    @if ($r->balance_due_at && in_array($r->status, ['balance_invoiced', 'balance_proof_submitted']))
      <div class="panel countdown" data-due="{{ $r->balance_due_at->toIso8601String() }}">
        <p class="eyebrow">Balance due {{ $r->balance_due_at->timezone('America/New_York')->format('F j, Y g:i a') }} ET</p>
        <p class="big mono" data-count aria-live="off">{{ $r->balance_due_at->isPast() ? 'Past due' : $r->balance_due_at->diffForHumans(['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}</p>
        <p class="hint">Your allocation milestone was confirmed {{ $r->milestone_confirmed_at->timezone('America/New_York')->format('M j, Y') }}. Wire the balance to escrow and upload your proof before the time runs out.</p>
      </div>
    @endif

    <h2>Invoices</h2>
    @foreach ($r->invoices as $inv)
      @php($pending = $inv->payments->firstWhere('status', 'pending'))
      @php($rejected = $inv->payments->where('status', 'rejected')->last())
      <div class="panel inv">
        <div class="row"><div><span class="mono">{{ $inv->number }}</span> <span class="hint">{{ $inv->title() }}</span></div><div><strong>{{ $money($inv->amount_cents) }}</strong> <span class="tag">{{ $inv->status }}</span></div></div>
        @if ($inv->due_at)<p class="hint">Due {{ $inv->due_at->timezone('America/New_York')->format('M j, Y') }}</p>@endif
        <p class="acts"><a class="link" href="{{ route('invoice.pdf', $inv) }}">Download PDF</a></p>
        @if ($inv->status === 'open')
          @if ($pending)
            <p class="hint">Proof under review. Submitted {{ $pending->submitted_at->timezone('America/New_York')->format('M j, g:i a') }} ET.</p>
          @else
            @if ($rejected)<p class="warn">We couldn't verify your last proof{{ $rejected->note ? ': '.$rejected->note : '' }}. Please upload a new one.</p>@endif
            <form method="post" action="{{ route('invoice.proof', $inv) }}" enctype="multipart/form-data" class="proof-form">@csrf
              <label for="proof-{{ $inv->id }}">Upload proof of wire</label>
              <input id="proof-{{ $inv->id }}" name="proof" type="file" required accept="image/png,image/jpeg,image/webp,application/pdf" class="field file">
              <button class="btn solid" type="submit">Send proof</button>
            </form>
            <p class="hint">PNG, JPG, WebP or PDF, up to 8 MB.</p>
          @endif
        @endif
      </div>
    @endforeach

    @include('member._escrow')
    @unless ($r->cancelled_at)@include('member._wire')@endunless
  @endif
</div>
<script>
(function(){var el=document.querySelector('.countdown');if(!el)return;var out=el.querySelector('[data-count]');var due=new Date(el.dataset.due).getTime();
function tick(){var d=due-Date.now();if(d<=0){out.textContent='Past due';return}var s=Math.floor(d/1000),dd=Math.floor(s/86400),h=Math.floor(s%86400/3600),m=Math.floor(s%3600/60),ss=s%60;out.textContent=dd+'d '+String(h).padStart(2,'0')+'h '+String(m).padStart(2,'0')+'m '+String(ss).padStart(2,'0')+'s';}
tick();setInterval(tick,1000);})();
</script>
@endsection
