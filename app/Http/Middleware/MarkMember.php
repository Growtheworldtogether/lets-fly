<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Leaves a small encrypted marker in the browser of anyone who has signed in. When that person's session later ends,
 * they are sent back to the code page. A stranger has no marker (and cannot make one: it is encrypted with the app key),
 * so every address behind the gate answers them with the same 404 as an address that does not exist.
 */
class MarkMember
{
    public const COOKIE = 'mk';

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->user() && $request->cookie(self::COOKIE) !== '1') {
            Cookie::queue(self::COOKIE, '1', 60 * 24 * 30);
        }

        return $response;
    }

    public static function present(Request $request): bool
    {
        return $request->cookie(self::COOKIE) === '1';
    }
}
