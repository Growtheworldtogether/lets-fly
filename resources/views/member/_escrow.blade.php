{{-- Where the member's money is: $r (with escrowEntries) --}}
@php
  $held = $r->heldCents();
  $money = fn ($c) => \App\Support\Money::cents((int) $c)->format();
  $sent = $r->deposit_verified_at || $r->payments->isNotEmpty();
  $settled = $r->escrowEntries->where('event', 'released')->isNotEmpty() && $held === 0;
  $steps = [
    ['Deposit invoiced', true],
    ['Wire sent', (bool) $sent],
    ['Deposit held in escrow', (bool) $r->deposit_verified_at],
    ['Milestone confirmed', (bool) $r->milestone_confirmed_at],
    ['Balance received in escrow', (bool) $r->balance_verified_at],
    ['Settled with '.brand('name'), $settled],
  ];
  $current = collect($steps)->search(fn ($s) => ! $s[1]);
@endphp
<h2>Where your funds are</h2>
<div class="panel escrow">
  @if ($r->cancelled_at)
    @php($why = ['cancelled_refunded' => 'Funds held in escrow were returned to you.', 'cancelled_released' => 'Funds held in escrow were released as set out in your agreement.'][$r->outcome] ?? 'No funds had been received.')
    <p class="warn">This reservation was cancelled. {{ $why }}</p>
  @else
    <ol class="steps">
      @foreach ($steps as $i => [$label, $done])
        <li class="{{ $done ? 'done' : ($i === $current ? 'now' : '') }}"><span>{{ $label }}</span></li>
      @endforeach
    </ol>
    <p class="hint">@if ($held > 0)<strong>{{ $money($held) }}</strong> is held in escrow by {{ \App\Services\Escrow::agent() }}.@else Nothing is held in escrow yet.@endif Your deposit stays in escrow until your allocation terms advance, then settlement is coordinated with {{ brand('name') }}.</p>
  @endif
</div>
