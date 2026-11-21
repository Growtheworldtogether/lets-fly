@php($money = fn ($c) => \App\Support\Money::cents((int) $c)->format())
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Invoice {{ $invoice->number }}</title></head>
<body style="margin:0;padding:0;background:#eeeeee;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eeeeee;"><tr><td align="center" style="padding:32px 12px;">
<table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:100%;background:#ffffff;border:1px solid #d8d8d8;border-radius:4px;">
  <tr><td style="background:#000000;padding:26px 36px;border-bottom:2px solid #d7a970;border-radius:4px 4px 0 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
      <td style="font-family:Arial,sans-serif;font-weight:700;font-size:18px;letter-spacing:6px;text-transform:uppercase;color:#ffffff;">{{ brand('name') }}</td>
      <td align="right" style="font-family:Arial,sans-serif;font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#bbbbbb;">Pioneer allocation</td>
    </tr></table>
  </td></tr>
  <tr><td style="padding:32px 36px 8px;">
    <div style="font-family:Arial,sans-serif;font-weight:300;font-size:32px;line-height:1.15;color:#000000;">Invoice</div>
    <div style="font-family:Arial,sans-serif;font-size:16px;line-height:1.5;color:#333333;margin-top:10px;">@if ($invoice->kind === 'deposit')Thank you, {{ $user->name }}. Your place among the pioneers of personal flight is held for you. This invoice is for your reservation deposit. Your wire goes to an escrow account, not to {{ brand('name') }}'s operating funds, and is held by {{ \App\Services\Escrow::agent() }} until your allocation terms advance.@else Good news, {{ $user->name }}: your allocation milestone has been confirmed. This invoice is for your balance, due within {{ \App\Services\Pricing::balanceDays() }} days. Wire it to the same escrow account.@endif</div>
  </td></tr>
  <tr><td style="padding:16px 36px 8px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #dddddd;border-bottom:1px solid #dddddd;font-family:Arial,sans-serif;">
      <tr>
        <td width="50%" style="padding:14px 0;vertical-align:top;"><div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#777777;font-weight:700;">Billed to</div>
          <div style="font-size:16px;color:#000000;font-weight:700;margin-top:4px;">{{ $user->name }}</div><div style="font-size:14px;color:#333333;">{{ $user->email }}</div></td>
        <td width="50%" style="padding:14px 0;vertical-align:top;"><table role="presentation" cellpadding="0" cellspacing="0" width="100%">
          <tr><td style="font-size:13px;color:#777777;padding:2px 0;">Invoice number</td><td align="right" style="font-family:'Courier New',monospace;font-size:14px;color:#000000;font-weight:700;">{{ $invoice->number }}</td></tr>
          <tr><td style="font-size:13px;color:#777777;padding:2px 0;">Issued</td><td align="right" style="font-size:14px;color:#000000;">{{ $invoice->issued_at->timezone('America/New_York')->format('F j, Y') }}</td></tr>
          <tr><td style="font-size:13px;color:#777777;padding:2px 0;">Member number</td><td align="right" style="font-family:'Courier New',monospace;font-size:14px;color:#000000;font-weight:700;">{{ $user->member_number }}</td></tr>
          <tr><td style="font-size:13px;color:#777777;padding:2px 0;">Reservation</td><td align="right" style="font-family:'Courier New',monospace;font-size:14px;color:#000000;font-weight:700;">{{ $r->reference }}</td></tr>
        </table></td>
      </tr>
    </table>
  </td></tr>
  <tr><td style="padding:20px 36px 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-family:Arial,sans-serif;">
      <tr><td style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#777777;font-weight:700;padding-bottom:8px;border-bottom:2px solid #000000;">Item</td>
          <td align="right" style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#777777;font-weight:700;padding-bottom:8px;border-bottom:2px solid #000000;">Amount (USD)</td></tr>
      <tr><td style="padding:16px 12px 16px 0;border-bottom:1px solid #eeeeee;vertical-align:top;">
            <div style="font-size:16px;color:#000000;font-weight:700;">{{ $invoice->title() }}</div>
            <div style="font-size:14px;color:#333333;margin-top:2px;">@if ($invoice->kind === 'deposit') Reserves your pioneer allocation. Due now. @else Due {{ $invoice->due_at?->timezone('America/New_York')->format('F j, Y') }}. @endif</div></td>
          <td align="right" style="padding:16px 0;border-bottom:1px solid #eeeeee;vertical-align:top;font-size:16px;color:#000000;font-weight:700;">{{ $money($invoice->amount_cents) }}</td></tr>
      @if ($invoice->kind === 'deposit')
      <tr><td style="padding:16px 12px 16px 0;border-bottom:1px solid #eeeeee;vertical-align:top;">
            <div style="font-size:16px;color:#000000;font-weight:700;">Installment 2: Balance</div>
            <div style="font-size:14px;color:#333333;margin-top:2px;">Due within {{ \App\Services\Pricing::balanceDays() }} days after {{ brand('name') }} confirms your allocation milestone. We will email the balance invoice and the exact date then.</div></td>
          <td align="right" style="padding:16px 0;border-bottom:1px solid #eeeeee;vertical-align:top;font-size:16px;color:#000000;font-weight:700;">{{ $money($r->balance_cents) }}</td></tr>
      <tr><td style="padding:16px 12px 4px 0;font-size:15px;color:#333333;">Pioneer allocation total</td>
          <td align="right" style="padding:16px 0 4px;font-size:24px;font-weight:600;color:#000000;">{{ $money($r->price_cents) }}</td></tr>
      <tr><td colspan="2" style="padding:0 0 4px;font-size:13px;color:#777777;">Public launch price in 2028 is projected at {{ $money(\App\Services\Pricing::publicPrice()) }}.</td></tr>
      @endif
    </table>
  </td></tr>
  <tr><td style="padding:24px 36px 32px;font-family:Arial,sans-serif;">
    <div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#777777;font-weight:700;">How to pay</div>
    <div style="font-size:15px;color:#333333;line-height:1.5;margin-top:6px;">Send a wire transfer to the escrow account and use your reservation code as the reference, exactly as shown. For your security, the bank and account details are not in this email: sign in with your invite code to see them on your dashboard, where you can also upload proof of the wire.</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;background:#f5f5f5;border-radius:4px;"><tr><td style="padding:12px 16px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
      @foreach (collect($lines)->whereIn(0, ['Escrow agent', 'Reference']) as [$label, $value])
      <tr><td width="130" style="width:130px;font-family:Arial,sans-serif;font-size:13px;color:#777777;padding:3px 12px 3px 0;white-space:nowrap;vertical-align:top;">{{ $label }}</td><td style="font-family:'Courier New',monospace;font-size:13px;color:#000000;padding:3px 0;">{{ $value }}</td></tr>
      @endforeach
      </table>
    </td></tr></table>
    <div style="margin-top:12px;font-size:13px;color:#777777;line-height:1.5;">Before sending your first wire, confirm the details on your dashboard with us at {{ brand('support_email') }}. We will never ask you to send money anywhere else.</div>
  </td></tr>
</table></td></tr></table>
</body></html>
