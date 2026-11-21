@php($reset = ($purpose ?? '') === 'reset')
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $reset ? 'Your password reset code' : 'Your sign-in code' }}</title></head>
<body style="margin:0;padding:0;background:#eeeeee;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eeeeee;"><tr><td align="center" style="padding:32px 12px;">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="width:560px;max-width:100%;background:#ffffff;border:1px solid #d8d8d8;border-radius:4px;">
  <tr><td style="background:#000000;padding:22px 32px;border-bottom:2px solid #d7a970;border-radius:4px 4px 0 0;font-family:Arial,sans-serif;font-weight:700;font-size:16px;letter-spacing:6px;text-transform:uppercase;color:#ffffff;">{{ brand('name') }}</td></tr>
  <tr><td style="padding:28px 32px;font-family:Arial,sans-serif;">
    <div style="font-size:22px;font-weight:300;color:#000000;margin-bottom:14px;">{{ $reset ? 'Your password reset code' : 'Your sign-in code' }}</div>
    <div style="font-size:15px;line-height:1.6;color:#333333;">Hello {{ $name }},</div>
    <div style="font-size:15px;line-height:1.6;color:#333333;margin-top:10px;">{{ $reset ? 'Enter this code to choose a new password.' : 'Enter this code to finish signing in.' }}</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;"><tr><td align="center" style="background:#f5f5f5;border:1px solid #e2e2e2;border-radius:4px;padding:20px 12px;font-family:'Courier New',monospace;font-size:34px;font-weight:700;letter-spacing:10px;color:#000000;"><strong style="font-weight:700;">{{ $code }}</strong></td></tr></table>
    <div style="font-size:15px;line-height:1.6;color:#333333;margin-top:18px;">It works for {{ $minutes }} minutes.</div>
    <div style="font-size:14px;line-height:1.6;color:#777777;margin-top:10px;">{{ $reset ? 'If you did not ask to reset your password, you can ignore this email: nothing has changed.' : 'If you did not try to sign in, you can ignore this email.' }}</div>
  </td></tr>
</table></td></tr></table>
</body></html>
