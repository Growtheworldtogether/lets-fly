<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgreementVersion;
use App\Services\Audit;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    public function edit()
    {
        return view('admin.agreement', ['current' => AgreementVersion::current(), 'count' => AgreementVersion::count()]);
    }

    /** Publishing never edits an old version: members who accepted version 1 stay on version 1. */
    public function publish(Request $request)
    {
        $d = $request->validate(['body' => ['required', 'string', 'min:50', 'max:30000']]);
        $v = AgreementVersion::create(['version' => AgreementVersion::current()->version + 1, 'body' => $d['body'], 'published_at' => now()]);
        Audit::record('agreement.published', $v, null, ['version' => $v->version], $request->user()->id);

        return back()->with('toast', 'Agreement version '.$v->version.' is now live for new reservations.');
    }
}
