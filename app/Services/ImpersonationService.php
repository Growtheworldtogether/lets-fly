<?php

namespace App\Services;

use App\Models\Impersonation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** "Log in as member": the session switches to the member and a banner stays on until it ends. */
final class ImpersonationService
{
    public function start(Request $request, User $admin, User $member): Impersonation
    {
        if ($request->session()->has('impersonation')) {
            throw ValidationException::withMessages(['member' => 'Exit the current member session first.']);
        }
        if ($member->isAdmin() || $member->is($admin)) {
            throw ValidationException::withMessages(['member' => 'You can only act as a member.']);
        }

        $imp = Impersonation::create([
            'admin_id' => $admin->id, 'member_id' => $member->id, 'ip' => $request->ip(),
            'started_at' => now(), 'expires_at' => now()->addMinutes(config('admin.impersonation_minutes')),
        ]);
        Audit::record('impersonation.started', $member, null, null, $admin->id);

        Auth::login($member);
        $request->session()->regenerate();
        $request->session()->put('impersonation', [
            'id' => $imp->id, 'admin_id' => $admin->id, 'member_id' => $member->id,
            'admin_name' => $admin->name, 'member_name' => $member->name, 'expires_at' => $imp->expires_at->timestamp,
        ]);

        return $imp;
    }

    public function end(Request $request, string $how = 'exited'): void
    {
        $data = $request->session()->get('impersonation');
        if (! $data) {
            return;
        }
        Impersonation::find($data['id'])?->update(['ended_at' => now(), 'ended_reason' => $how]);
        Audit::record('impersonation.ended', User::find($data['member_id']), null, ['how' => $how], $data['admin_id'], $data['admin_id']);

        $request->session()->forget('impersonation');
        Auth::loginUsingId($data['admin_id']);
        $request->session()->regenerate();
        $request->session()->put('staff_last_activity', time());
    }
}
