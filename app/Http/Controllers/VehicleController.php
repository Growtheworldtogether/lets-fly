<?php

namespace App\Http\Controllers;

use App\Models\PageSection;

class VehicleController extends Controller
{
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
