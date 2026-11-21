<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // First: until setup has run, every page leads to /setup (and files stand in for the database).
        $middleware->web(prepend: [\App\Http\Middleware\RequireSetup::class]);
        // Someone who has signed in before goes back to the code page when the session ends; a stranger gets the same
        // 404 as for an address that does not exist, so the pages behind the gate cannot be mapped from outside.
        $middleware->redirectGuestsTo(fn (Request $request) => \App\Http\Middleware\MarkMember::present($request) ? '/' : abort(404));
        $middleware->alias([
            'staff' => \App\Http\Middleware\EnsureStaff::class,
            'gate' => \App\Http\Middleware\RequireGate::class,
        ]);
        // The update pages stay reachable while the site is down for an update.
        $middleware->preventRequestsDuringMaintenance(except: ['admin/system', 'admin/system/*']);
        $middleware->appendToGroup('web', \App\Http\Middleware\ApplyImpersonation::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\MarkMember::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);
        $middleware->redirectUsersTo(fn () => auth()->user()?->isAdmin() ? '/admin' : '/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // For a stranger, a rejected form post (419) or a wrong method on a real address (405) would show which addresses
        // exist, because a missing address is a 404. Outside the code page's own form they get the same 404 page.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if (! in_array($e->getStatusCode(), [405, 419], true)) {
                return null;
            }
            $known = $request->user() || ($request->hasSession() && $request->session()->has('gate.invite'));
            if (! $known && ! $request->is('enter')) {
                return response()->view('errors.404', [], 404);
            }

            return null;
        });
        // A request that asks for JSON must not get a "please sign in" (401) that a missing address would not give.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            return \App\Http\Middleware\MarkMember::present($request) ? null : response()->view('errors.404', [], 404);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
