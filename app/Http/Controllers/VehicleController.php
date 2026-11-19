<?php

namespace App\Http\Controllers;

use App\Models\PageSection;

class VehicleController extends Controller
{
    public function index()
    {
        PageSection::ensureDefaults();
        $sections = PageSection::with(['image', 'poster'])->where('active', true)->orderBy('sort')->get();

        return view('member.vehicle', ['hero' => $sections->firstWhere('slug', 'showcase'), 'sections' => $sections->where('slug', '!=', 'showcase')->values()]);
    }

    public function show(string $slug)
    {
        PageSection::ensureDefaults();
        $all = PageSection::with(['image', 'poster'])->where('active', true)->where('slug', '!=', 'showcase')->orderBy('sort')->get();
        $i = $all->search(fn ($s) => $s->slug === $slug);
        abort_if($i === false, 404);

        return view('member.vehicle-section', ['s' => $all[$i], 'prev' => $all[$i - 1] ?? null, 'next' => $all[$i + 1] ?? null]);
    }
}
