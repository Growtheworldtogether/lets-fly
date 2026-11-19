<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $rows = Reservation::with('user')->latest('id')->when($request->query('q'), function ($q, $s) {
            $q->where(fn ($w) => $w->where('reference', 'like', "%$s%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$s%")->orWhere('member_number', 'like', "%$s%")));
        })->paginate(25)->withQueryString();

        return view('admin.reservations', ['rows' => $rows]);
    }
}
