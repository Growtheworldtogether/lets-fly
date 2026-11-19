<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && $user->isAdmin() && $user->adminProfile, 403);
        $last = $request->session()->get('staff_last_activity');
        if ($last && time() - $last > config('admin.idle_timeout_minutes') * 60) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('gate')->with('error', 'You were signed out after a period of inactivity.');
        }
        $request->session()->put('staff_last_activity', time());

        return $next($request);
    }
}
