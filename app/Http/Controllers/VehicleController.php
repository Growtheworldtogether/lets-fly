<?php

namespace App\Http\Controllers;

use App\Models\PageSection;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /** Shown once, right after a member first signs in; the button leads on to the vehicle. */
    public function welcome(Request $request)
    {
        return view('member.welcome', ['user' => $request->user()]);
    }

    public function welcomed(Request $request)
    {
        $user = $request->user();
        // An admin looking in as a member must not mark the member's welcome as seen.
        if (! $request->session()->has('impersonation') && ! $user->intro_seen_at) {
            $user->forceFill(['intro_seen_at' => now()])->saveQuietly();
        }

        return redirect()->route('vehicle');
    }

    /** One long page, like a showroom: hero, key numbers, then each section full screen. */
    public function index()
    {
        PageSection::ensureDefaults();
        $all = PageSection::with(['image', 'poster'])->where('active', true)->orderBy('sort')->get();

        return view('member.vehicle', [
            'hero' => $all->firstWhere('slug', 'showcase'),
            'sections' => $all->where('slug', '!=', 'showcase')->values(),
        ]);
    }

    /** Older links to a single section now jump to it on the long page. */
    public function show(string $slug)
    {
        PageSection::ensureDefaults();
        abort_unless(PageSection::where('active', true)->where('slug', $slug)->where('slug', '!=', 'showcase')->exists(), 404);

        return redirect(route('vehicle').'#'.$slug);
    }
}
