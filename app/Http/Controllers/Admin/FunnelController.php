<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class FunnelController extends Controller
{
    public const STEPS = [
        'created' => 'Invite created', 'opened' => 'Code entered', 'claimed' => 'Account created', 'agreed' => 'Agreement accepted',
        'invoiced' => 'Deposit invoiced', 'proof_uploaded' => 'Proof of wire uploaded', 'deposit_paid' => 'Deposit verified', 'balance_paid' => 'Balance verified',
    ];

    public function __invoke()
    {
        $counts = DB::table('invite_events')->select('type', DB::raw('count(distinct invite_id) as n'))->groupBy('type')->pluck('n', 'type');
        $rows = [];
        $top = max(1, (int) ($counts['created'] ?? 0));
        foreach (self::STEPS as $key => $label) {
            $n = (int) ($counts[$key] ?? 0);
            $rows[] = ['label' => $label, 'n' => $n, 'pct' => round($n / $top * 100)];
        }

        return view('admin.funnel', ['rows' => $rows]);
    }
}
