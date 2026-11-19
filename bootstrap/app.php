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
        $middleware->redirectGuestsTo('/');
        $middleware->alias([
            'staff' => \App\Http\Middleware\EnsureStaff::class,
            'gate' => \App\Http\Middleware\RequireGate::class,
        ]);
        // The update pages stay reachable while the site is down for an update.
        $middleware->preventRequestsDuringMaintenance(except: ['admin/system', 'admin/system/*']);
        $middleware->appendToGroup('web', \App\Http\Middleware\ApplyImpersonation::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);
        $middleware->redirectUsersTo(fn () => auth()->user()?->isAdmin() ? '/admin' : '/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
