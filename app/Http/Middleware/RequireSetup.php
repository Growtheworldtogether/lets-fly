<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;

/** Until setup has run, every page leads to /setup, and /setup works without a database. Afterwards /setup is gone. */
class RequireSetup
{
    public function handle(Request $request, Closure $next)
    {
        $isSetup = $request->is('setup', 'setup/*');

        if (Installer::installed()) {
            abort_if($isSetup, 404);

            return $next($request);
        }

        // A fresh copy has no app key yet. Make one so cookies and sessions work during setup.
        if (! config('app.key') && ! app()->runningUnitTests()) {
            $key = 'base64:'.base64_encode(random_bytes(32));
            Installer::writeEnv(['APP_KEY' => $key]);
            config(['app.key' => $key]);
        }

        // No database yet, so sessions and cache live in files for now.
        if (! app()->runningUnitTests()) {
            config(['session.driver' => 'file', 'cache.default' => 'file']);
        }

        if ($isSetup || $request->is('up')) {
            return $next($request);
        }

        return redirect('/setup');
    }
}
