<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Models\User;
use App\Services\Audit;
use App\Services\InviteService;
use App\Services\StaffDirectory;
use App\Support\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

/**
 * Step two after the invite code. A first visit claims the account (set a password); later visits ask for the
 * password. Members are asked for an emailed code after several wrong passwords or from another country.
 */
class SignInController extends Controller
{
    private function invite(Request $request): Invite
    {
        $invite = Invite::find($request->session()->get('gate.invite'));
        abort_unless($invite && $invite->isUsable(), 404);

        return $invite;
    }

    public function show(Request $request)
    {
        $invite = $this->invite($request);
        if ($request->session()->has('signin.user')) {
            return view('auth.code', ['invite' => $invite]);
        }

        return view($invite->status === 'active' ? 'auth.claim' : 'auth.signin', ['invite' => $invite]);
    }

    public function claim(Request $request, InviteService $invites)
    {
        $invite = $this->invite($request);
        abort_unless($invite->status === 'active', 404);
        $data = $request->validate([
            'email' => $invite->email ? ['nullable'] : ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);
        $email = $invite->email ?: strtolower($data['email']);
        abort_if(User::where('email', $email)->exists(), 422, 'That email is already in use.');

        $user = User::create([
            'name' => $invite->name,
            'email' => $email,
            'password' => $data['password'],
            'role' => $invite->kind === 'admin' ? 'admin' : 'member',
            'member_number' => $invite->kind === 'admin' ? null : $invites->nextMemberNumber(),
            'signup_country' => Country::of($request),
            'email_verified_at' => now(),
        ]);
        if ($invite->kind === 'admin') {
            StaffDirectory::assign($user, 'Super Admin');
        }
        $invite->update(['status' => 'claimed', 'user_id' => $user->id, 'claimed_at' => now()]);
        $invites->log('claimed', $invite, $user);

        return $this->finish($request, $user);
    }

    public function signIn(Request $request)
    {
        $invite = $this->invite($request);
        abort_unless($invite->status === 'claimed' && $invite->user, 404);
        $user = $invite->user;
        $request->validate(['password' => ['required', 'string', 'max:200']]);

        if ($user->locked_until?->isFuture() || in_array($user->status, ['closed', 'deceased'], true)) {
            return back()->with('error', 'This account is paused. Try again in a few minutes.');
        }
        if (! Hash::check($request->string('password'), $user->password)) {
            $n = $user->failed_logins + 1;
            $user->forceFill(['failed_logins' => $n, 'locked_until' => $n >= config('pioneer.max_failed') ? now()->addMinutes(config('pioneer.lock_minutes')) : null])->saveQuietly();
            Audit::record('login.failed', $user, null, null, $user->id);

            return back()->with('error', $n >= config('pioneer.max_failed')
                ? 'Too many wrong attempts. This account is paused for a few minutes.'
                : 'That password is not right.');
        }

        // Members need an emailed code after several wrong passwords, or from a different country than they joined from.
        $country = Country::of($request);
        $risky = ! $user->isAdmin() && (
            $user->failed_logins >= config('pioneer.code_after_failed')
            || (\App\Models\Setting::bool('country_check', config('pioneer.country_check')) && $user->signup_country && $country && $country !== $user->signup_country)
        );
        if ($risky) {
            $request->session()->put('signin.user', $user->id);
            $this->sendCode($request, $user);

            return redirect()->route('signin')->with('sent', 'We emailed you a 6-digit code.');
        }

        return $this->finish($request, $user);
    }

    public function code(Request $request)
    {
        $this->invite($request);
        $user = User::find($request->session()->get('signin.user'));
        abort_unless($user, 404);
        $request->validate(['code' => ['required', 'digits:6']]);
        $hash = $request->session()->get('signin.code');
        $expires = $request->session()->get('signin.expires', 0);
        $tries = $request->session()->increment('signin.tries');
        if ($tries > 5 || time() > $expires || ! $hash || ! hash_equals($hash, hash('sha256', $request->string('code'))) ) {
            if ($tries > 5) {
                $request->session()->forget(['signin']);

                return redirect()->route('signin')->with('error', 'Too many wrong codes. Enter your password again.');
            }

            return back()->with('error', 'That code is not right or has expired.');
        }

        return $this->finish($request, $user);
    }

    public function resend(Request $request)
    {
        $this->invite($request);
        $user = User::find($request->session()->get('signin.user'));
        abort_unless($user, 404);
        if (time() - (int) $request->session()->get('signin.sent_at', 0) < 30) {
            return back()->with('error', 'Please wait a moment before asking for another code.');
        }
        $this->sendCode($request, $user);

        return back()->with('sent', 'A new code is on its way.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('gate');
    }

    private function sendCode(Request $request, User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $request->session()->put([
            'signin.code' => hash('sha256', $code),
            'signin.expires' => time() + config('pioneer.code_minutes') * 60,
            'signin.sent_at' => time(),
            'signin.tries' => 0,
        ]);
        Mail::send('mail.signin-code', ['code' => $code, 'name' => $user->name, 'minutes' => config('pioneer.code_minutes')], function ($m) use ($user) {
            $m->to($user->email)->subject('Your sign-in code');
        });
    }

    private function finish(Request $request, User $user)
    {
        $inviteId = $request->session()->get('gate.invite');
        $request->session()->flush();
        $request->session()->regenerate();
        Auth::login($user);
        $user->forceFill([
            'failed_logins' => 0, 'locked_until' => null, 'last_login_at' => now(),
            'last_country' => Country::of($request),
            'signup_country' => $user->signup_country ?: Country::of($request),
        ])->saveQuietly();
        Audit::record('login.success', $user, null, null, $user->id);

        if ($user->isAdmin()) {
            return redirect()->intended('/admin');
        }

        // A member's very first visit starts on the welcome page; after that they land on the vehicle.
        return $user->intro_seen_at ? redirect()->intended('/vehicle') : redirect()->route('welcome');
    }
}
