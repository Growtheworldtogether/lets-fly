<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Services\Audit;
use App\Services\InviteService;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    public function index(Request $request)
    {
        $invites = Invite::with('user')->latest('id')->when($request->query('q'), function ($q, $s) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('label', 'like', "%$s%"));
        })->paginate(25)->withQueryString();

        return view('admin.invites', ['invites' => $invites]);
    }

    public function store(Request $request, InviteService $service)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['nullable', 'email:rfc', 'max:190', 'unique:users,email'],
            'label' => ['nullable', 'string', 'max:120'],
            'kind' => ['required', 'in:member,admin'],
        ]);
        if ($data['kind'] === 'admin') {
            abort_unless($request->user()->can('admin.manage'), 403);
        }
        [$invite, $code] = $service->create($data['name'], $data['email'] ?? null, $data['kind'], $data['label'] ?? null, $request->user());
        Audit::record('invite.created', $invite, null, ['kind' => $invite->kind], $request->user()->id);

        return redirect()->route('admin.invites')->with('new_code', ['name' => $invite->name, 'code' => $code]);
    }

    public function regenerate(Request $request, Invite $invite, InviteService $service)
    {
        abort_if($invite->kind === 'admin' && ! $request->user()->can('admin.manage'), 403);
        $code = $service->regenerate($invite, $request->user());
        Audit::record('invite.regenerated', $invite, null, null, $request->user()->id);

        return redirect()->route('admin.invites')->with('new_code', ['name' => $invite->name, 'code' => $code]);
    }

    public function revoke(Request $request, Invite $invite)
    {
        abort_if($invite->kind === 'admin' && ! $request->user()->can('admin.manage'), 403);
        $invite->update(['status' => 'revoked', 'revoked_at' => now()]);
        app(InviteService::class)->log('revoked', $invite, $request->user());
        Audit::record('invite.revoked', $invite, null, null, $request->user()->id);

        return back()->with('toast', 'Invite revoked. Its code no longer works.');
    }

    public function export()
    {
        Audit::record('invite.exported', null, null, null, request()->user()->id);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Kind', 'Status', 'Code ends in', 'Label', 'Created', 'Claimed']);
            Invite::orderBy('id')->each(fn ($i) => fputcsv($out, [
                self::safe($i->name), $i->email, $i->kind, $i->status, $i->code_hint, self::safe((string) $i->label),
                $i->created_at, $i->claimed_at,
            ]));
            fclose($out);
        }, 'invites-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** Stops a spreadsheet from running a cell that starts with = + - or @. */
    public static function safe(string $v): string
    {
        return preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;
    }
}
