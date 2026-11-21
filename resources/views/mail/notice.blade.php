<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $heading }}</title></head>
<body style="margin:0;padding:0;background:#eeeeee;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eeeeee;"><tr><td align="center" style="padding:32px 12px;">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="width:560px;max-width:100%;background:#ffffff;border:1px solid #d8d8d8;border-radius:4px;">
  <tr><td style="background:#000000;padding:22px 32px;border-bottom:2px solid #d7a970;border-radius:4px 4px 0 0;font-family:Arial,sans-serif;font-weight:700;font-size:16px;letter-spacing:6px;text-transform:uppercase;color:#ffffff;">{{ brand('name') }}</td></tr>
  <tr><td style="padding:28px 32px;font-family:Arial,sans-serif;">
    <div style="font-size:22px;font-weight:300;color:#000000;margin-bottom:14px;">{{ $heading }}</div>
    <div style="font-size:15px;line-height:1.6;color:#333333;">Hello {{ $name }},</div>
    @foreach ($lines as $l)<div style="font-size:15px;line-height:1.6;color:#333333;margin-top:10px;">{{ $l }}</div>@endforeach
  </td></tr>
</table></td></tr></table>
</body></html>
