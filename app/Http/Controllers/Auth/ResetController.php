<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Models\User;
use App\Services\Audit;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

/** Forgot password, behind the invite code. A 6-digit code goes to the email on the account. Admins reset by command. */
class ResetController extends Controller
{
    private function member(Request $request): User
    {
        $invite = Invite::find($request->session()->get('gate.invite'));
        abort_unless($invite && $invite->isUsable() && $invite->kind === 'member' && $invite->user, 404);

        return $invite->user;
    }

    public function show(Request $request)
    {
        $user = $this->member($request);

        return view('auth.reset', ['user' => $user, 'sent' => $request->session()->has('reset.code')]);
    }

    public function send(Request $request)
    {
        $user = $this->member($request);
        if (time() - (int) $request->session()->get('reset.sent_at', 0) < 30) {
            return back()->with('error', 'Please wait a moment before asking for another code.');
        }
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $request->session()->put(['reset.code' => hash('sha256', $code), 'reset.expires' => time() + config('pioneer.code_minutes') * 60, 'reset.sent_at' => time(), 'reset.tries' => 0]);
        try {
            Mail::send('mail.signin-code', ['code' => $code, 'name' => $user->name, 'minutes' => config('pioneer.code_minutes')], fn ($m) => $m->to($user->email)->subject('Your password reset code'));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('reset')->with('sent', 'We emailed a 6-digit code to the address on your account.');
    }

    public function update(Request $request)
    {
        $user = $this->member($request);
        $d = $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);
        $tries = $request->session()->increment('reset.tries');
        $hash = $request->session()->get('reset.code');
        if ($tries > 5) {
            $request->session()->forget('reset');

            return redirect()->route('reset')->with('error', 'Too many wrong codes. Ask for a new one.');
        }
        if (! $hash || time() > $request->session()->get('reset.expires', 0) || ! hash_equals($hash, hash('sha256', $d['code']))) {
            return back()->with('error', 'That code is not right or has expired.');
        }

        $user->forceFill(['password' => $d['password'], 'failed_logins' => 0, 'locked_until' => null])->save();
        $request->session()->forget('reset');
        Audit::record('password.reset', $user, null, null, $user->id);
        app(PaymentService::class)->notice($user, 'Your password was changed', ['If this was not you, contact support right away.']);

        return redirect()->route('signin')->with('toast', 'Password changed. Sign in with your new password.');
    }
}
