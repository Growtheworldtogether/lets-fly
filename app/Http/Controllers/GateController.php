<?php

namespace App\Http\Controllers;

use App\Services\InviteService;
use Illuminate\Http\Request;

/** The only public page: a box for the invite code. */
class GateController extends Controller
{
    public function show(Request $request)
    {
        if ($request->user()) {
            return redirect($request->user()->isAdmin() ? '/admin' : '/dashboard');
        }

        return view('auth.gate');
    }

    public function enter(Request $request, InviteService $invites)
    {
        $request->validate(['code' => ['required', 'string', 'max:40']]);
        $invite = $invites->find($request->string('code'));
        if (! $invite) {
            return back()->withInput([])->with('error', 'That code is not valid. Check it and try again.');
        }
        if ($invite->locked_until?->isFuture()) {
            return back()->with('error', 'Too many wrong attempts. Try again in a few minutes.');
        }
        $request->session()->regenerate();
        $request->session()->put('gate.invite', $invite->id);
        if ($invite->status === 'active') {
            $invites->log('opened', $invite);
        }

        return redirect()->route('signin');
    }

    public function leave(Request $request)
    {
        $request->session()->forget(['gate.invite', 'signin']);

        return redirect()->route('gate');
    }
}
