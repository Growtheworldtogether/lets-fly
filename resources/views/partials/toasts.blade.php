{{-- Toast notifications. Server messages (session status, error, sent, toast) and anything pushed to the "toast" stack
     render here; window.toast(message, type) adds more from script. --}}
@php
    $slugs = [
        'verification-link-sent' => 'A new verification link is on its way.',
        'password-updated' => 'Your password has been changed.',
        'profile-information-updated' => 'Your contact details are saved.',
        'two-factor-authentication-enabled' => 'Two-step verification setup started. Confirm a code to finish.',
        'two-factor-authentication-confirmed' => 'Two-step verification is on.',
        'two-factor-authentication-disabled' => 'Two-step verification is off.',
        'recovery-codes-generated' => 'New recovery codes are ready.',
    ];
    $queue = [];
    if ($m = session('status')) { $queue[] = ['ok', $slugs[$m] ?? $m]; }
    if ($m = session('sent')) { $queue[] = ['ok', $m]; }
    if ($m = session('error')) { $queue[] = ['bad', $m]; }
    if ($t = session('toast')) { $queue[] = [$t['type'] ?? 'info', $t['message'] ?? '']; }
    // A form that came back with field errors: one red toast says so, and the message stays beside the field.
    if (! collect($queue)->contains(fn ($q) => $q[0] === 'bad')) {
        $fieldErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all())->unique()->values();
        if ($fieldErrors->isNotEmpty()) {
            $queue[] = ['bad', $fieldErrors->count() === 1 ? $fieldErrors->first() : 'Please fix the highlighted fields.'];
        }
    }
    $icons = [
        'ok' => '<path d="M5 12.5l4.2 4.2L19 7"/>',
        'bad' => '<path d="M12 7v6M12 17h.01"/><circle cx="12" cy="12" r="9"/>',
        'warn' => '<path d="M12 8v5M12 16.5h.01"/><path d="M12 3l10 18H2z"/>',
        'info' => '<path d="M12 11v6M12 7.5h.01"/><circle cx="12" cy="12" r="9"/>',
    ];
@endphp
<div class="toasts" id="toasts" aria-live="polite" aria-atomic="false">
    @foreach ($queue as [$type, $message])
        <div class="toast {{ $type }}" role="{{ $type === 'bad' ? 'alert' : 'status' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true">{!! $icons[$type] ?? $icons['info'] !!}</svg>
            <p>{{ $message }}</p>
            <button type="button" class="toast-x" aria-label="Dismiss">&times;</button>
        </div>
    @endforeach
    @stack('toast')
</div>
<script src="{{ asset('js/toast.js') }}" defer></script>
