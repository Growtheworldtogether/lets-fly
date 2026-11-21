<p>Hello {{ $name }},</p>
@if (($purpose ?? '') === 'reset')
<p>Your password reset code is <strong style="font-size:22px;letter-spacing:4px">{{ $code }}</strong>.</p>
<p>It works for {{ $minutes }} minutes. If you did not ask to reset your password, you can ignore this email: nothing has changed.</p>
@else
<p>Your sign-in code is <strong style="font-size:22px;letter-spacing:4px">{{ $code }}</strong>.</p>
<p>It works for {{ $minutes }} minutes. If you did not try to sign in, you can ignore this email.</p>
@endif
