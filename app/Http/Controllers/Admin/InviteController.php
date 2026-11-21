<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Services\Audit;
use App\Services\BulkInvites;
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

    /** Step 1 of pasting many names: show what will happen, so a typo is caught before codes exist. */
    public function bulkReview(Request $request)
    {
        $d = $this->bulkInput($request);
        $result = BulkInvites::parse($d['names'], $d['include_known']);
        if ($result['rows'] === []) {
            return back()->withInput()->withErrors(['names' => 'Paste at least one name, one per line.']);
        }

        return view('admin.invites-bulk-review', $d + $result + ['create' => collect($result['rows'])->where('create', true)->count()]);
    }

    /** Step 2: make the invites. The codes are kept in the session for 15 minutes so a refresh does not lose them. */
    public function bulkStore(Request $request, InviteService $service)
    {
        $d = $this->bulkInput($request);
        $result = BulkInvites::parse($d['names'], $d['include_known']);
        $items = [];
        \Illuminate\Support\Facades\DB::transaction(function () use ($result, $d, $service, $request, &$items) {
            foreach ($result['rows'] as $row) {
                if (! $row['create']) {
                    continue;
                }
                [$invite, $code] = $service->create($row['name'], $row['email'], 'member', $d['label'], $request->user());
                $items[] = ['name' => $invite->name, 'email' => $invite->email, 'code' => $code];
            }
        });
        if ($items === []) {
            return redirect()->route('admin.invites')->withErrors(['names' => 'No invites were created.']);
        }
        Audit::record('invite.bulk_created', null, null, ['count' => count($items), 'label' => $d['label']], $request->user()->id);
        $request->session()->put('bulk_invites', ['at' => now()->timestamp, 'label' => $d['label'], 'items' => $items]);

        return redirect()->route('admin.invites.bulk.done');
    }

    public function bulkDone(Request $request)
    {
        $b = $this->bulkSession($request);
        if (! $b) {
            return redirect()->route('admin.invites')->with('toast', 'Those codes are no longer shown. Use "New code" for anyone who still needs one.');
        }

        return view('admin.invites-bulk-done', ['items' => $b['items'], 'label' => $b['label']]);
    }

    public function bulkCsv(Request $request)
    {
        $b = $this->bulkSession($request);
        abort_unless($b, 404);
        Audit::record('invite.bulk_downloaded', null, null, ['count' => count($b['items'])], $request->user()->id);

        return response()->streamDownload(function () use ($b) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Invite code', 'Note']);
            foreach ($b['items'] as $i) {
                fputcsv($out, [self::safe($i['name']), $i['email'], $i['code'], self::safe((string) $b['label'])]);
            }
            fclose($out);
        }, 'invite-codes-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function bulkClear(Request $request)
    {
        $request->session()->forget('bulk_invites');

        return redirect()->route('admin.invites')->with('toast', 'The codes are hidden. Use "New code" if anyone still needs one.');
    }

    private function bulkSession(Request $request): ?array
    {
        $b = $request->session()->get('bulk_invites');
        if (! is_array($b) || now()->timestamp - ($b['at'] ?? 0) > 900) {
            $request->session()->forget('bulk_invites');

            return null;
        }

        return $b;
    }

    /** @return array{names: string, label: ?string, include_known: bool} */
    private function bulkInput(Request $request): array
    {
        $d = $request->validate(['names' => ['required', 'string', 'max:30000'], 'label' => ['nullable', 'string', 'max:120'], 'include_known' => ['nullable', 'boolean']], ['names.required' => 'Paste at least one name, one per line.']);

        return ['names' => $d['names'], 'label' => $d['label'] ?? null, 'include_known' => (bool) ($d['include_known'] ?? false)];
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
