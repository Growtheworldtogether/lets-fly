<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $members = User::where('role', 'member')->when($request->query('q'), function ($q, $s) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('member_number', 'like', "%$s%"));
        })->latest('id')->paginate(25)->withQueryString();

        return view('admin.members', ['members' => $members]);
    }

    public function impersonate(Request $request, User $user, ImpersonationService $svc)
    {
        $svc->start($request, $request->user(), $user);

        return redirect()->route('dashboard')->with('toast', 'You are now signed in as '.$user->name.'.');
    }

    public function exit(Request $request, ImpersonationService $svc)
    {
        abort_unless($request->session()->has('impersonation'), 404);
        $svc->end($request);

        return redirect()->route('admin.members')->with('toast', 'Back to your own account.');
    }
}
