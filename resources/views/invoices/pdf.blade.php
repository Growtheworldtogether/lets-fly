@php($money = fn ($c) => \App\Support\Money::cents((int) $c)->format())
<!DOCTYPE html><html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,Helvetica,Arial,sans-serif;font-size:12px;color:#111}
h1{font-weight:normal;font-size:28px;margin:0 0 4px}.muted{color:#777}.mono{font-family:DejaVu Sans Mono,monospace}
table{width:100%;border-collapse:collapse}th{text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#777;border-bottom:2px solid #000;padding:6px 0}
td{padding:10px 0;border-bottom:1px solid #ddd;vertical-align:top}.r{text-align:right}.head{background:#000;color:#fff;border-bottom:3px solid #d7a970;padding:14px 18px;margin-bottom:24px;letter-spacing:4px;text-transform:uppercase;font-weight:bold}
.wire{background:#f3f3f3;padding:10px 12px;margin-top:6px}.foot{margin-top:36px;font-size:10px;color:#888;border-top:1px solid #ddd;padding-top:8px}
</style></head><body>
<div class="head">{{ brand('name') }}</div>
<h1>Invoice</h1><div class="muted">{{ $invoice->number }} &middot; issued {{ $invoice->issued_at->timezone('America/New_York')->format('F j, Y') }}</div>
<table style="margin:18px 0"><tr><td style="border:0;width:50%"><div class="muted">BILLED TO</div><strong>{{ $user->name }}</strong><br>{{ $user->email }}</td>
<td style="border:0" class="r"><div class="muted">MEMBER NUMBER</div><strong class="mono">{{ $user->member_number }}</strong><br><span class="muted">Reservation</span> <span class="mono">{{ $r->reference }}</span></td></tr></table>
<table><tr><th>Item</th><th class="r">Amount (USD)</th></tr>
<tr><td><strong>{{ $invoice->title() }}</strong><br><span class="muted">@if ($invoice->kind === 'deposit') Reserves your pioneer allocation. Due now. @else Due {{ $invoice->due_at?->timezone('America/New_York')->format('F j, Y') }}. @endif</span></td><td class="r"><strong>{{ $money($invoice->amount_cents) }}</strong></td></tr>
@if ($invoice->kind === 'deposit')
<tr><td><strong>Installment 2: Balance</strong><br><span class="muted">Due within {{ \App\Services\Pricing::balanceDays() }} days after your allocation milestone is confirmed.</span></td><td class="r"><strong>{{ $money($r->balance_cents) }}</strong></td></tr>
<tr><td class="r" style="border:0">Pioneer allocation total</td><td class="r" style="border:0;font-size:16px"><strong>{{ $money($r->price_cents) }}</strong></td></tr>
@endif</table>
<p style="margin-top:20px"><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
<div class="muted">HOW TO PAY</div><p>Wire the amount to the escrow account below, held by {{ \App\Services\Escrow::agent() }}. Use your reservation code as the reference, then upload proof of the wire in your dashboard.</p>
<div class="wire"><table style="width:100%">@foreach ($lines as [$label, $value])<tr><td style="border:0;padding:2px 10px 2px 0;width:32%" class="muted">{{ $label }}</td><td style="border:0;padding:2px 0" class="mono">{{ $value }}</td></tr>@endforeach</table>@if (trim($notes) !== '')<div class="mono" style="margin-top:6px">{!! nl2br(e($notes)) !!}</div>@endif</div>
<p class="muted" style="font-size:10px">Confirm these details with {{ brand('support_email') }} before sending your first wire.</p>
<div class="foot">{{ brand('footer') }}</div>
</body></html>
