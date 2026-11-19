<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Everything except the code page is hidden until a valid invite code has been entered in this session. */
class RequireGate
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() && ! $request->session()->has('gate.invite')) {
            abort(404);
        }

        return $next($request);
    }
}
