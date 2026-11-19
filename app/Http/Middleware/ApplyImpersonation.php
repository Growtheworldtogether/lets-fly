<?php

namespace App\Http\Middleware;

use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Ends "log in as member" sessions that have run out of time. */
class ApplyImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        $imp = $request->hasSession() ? $request->session()->get('impersonation') : null;
        if ($imp && now()->timestamp > $imp['expires_at']) {
            app(ImpersonationService::class)->end($request, 'expired');

            return redirect()->route('admin.home')->with('status', 'The member session expired after 30 minutes.');
        }

        return $next($request);
    }
}
