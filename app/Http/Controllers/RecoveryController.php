<?php

namespace App\Http\Controllers;

use App\Services\AdminRecovery;
use App\Services\InviteService;
use Illuminate\Http\Request;

/** Admin recovery for hosts without a command line. A 404 unless the owner has put the recovery file on the server. */
class RecoveryController extends Controller
{
    public function show()
    {
        abort_unless(AdminRecovery::secret() !== null, 404);

        return view('recover.form', ['file' => 'storage/app/recovery.txt']);
    }

    public function save(Request $request, InviteService $invites)
    {
        abort_unless(AdminRecovery::secret() !== null, 404);
        $v = $request->validate([
            'secret' => ['required', 'string', 'max:200'],
            'action' => ['required', 'in:invite,password'],
            'email' => ['required', 'email', 'max:160'],
            'name' => ['nullable', 'string', 'max:120'],
            'password' => ['required_if:action,password', 'nullable', 'string', 'min:10', 'max:200', 'regex:/[A-Za-z]/', 'regex:/\d/'],
        ], ['password.regex' => 'Use at least one letter and one number.', 'password.min' => 'Use at least 10 characters.']);
        AdminRecovery::check($v['secret'], (string) $request->ip());

        if ($v['action'] === 'password') {
            AdminRecovery::newPassword($v['email'], $v['password']);
            AdminRecovery::close();

            return response()->view('recover.done', ['code' => null, 'email' => strtolower($v['email'])])->header('Cache-Control', 'no-store');
        }
        $code = AdminRecovery::newInvite($v['name'] ?: 'Administrator', $v['email'], $invites);
        AdminRecovery::close();

        return response()->view('recover.done', ['code' => $code, 'email' => strtolower($v['email'])])->header('Cache-Control', 'no-store');
    }
}
