<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Audit;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public const TEXT = ['company_name', 'vehicle_name', 'support_email', 'sender_name', 'footer_text', 'member_prefix'];

    public function edit()
    {
        $s = [];
        foreach (self::TEXT as $k) {
            $s[$k] = Setting::find($k)?->value ?? '';
        }

        return view('admin.settings', [
            's' => $s,
            'digits' => Setting::value('member_digits', config('pioneer.member_digits')),
            'next' => Setting::value('member_next', 1),
            'country' => Setting::bool('country_check', config('pioneer.country_check')),
            'price' => Setting::value('price_cents', config('pioneer.price_cents')) / 100,
            'public_price' => Setting::value('public_price_cents', config('pioneer.public_price_cents')) / 100,
            'deposit' => Setting::value('deposit_cents', config('pioneer.deposit_cents')) / 100,
            'days' => Setting::value('balance_days', config('pioneer.balance_days')),
            'wire' => Setting::find('wire_details')?->value ?? '',
        ]);
    }

    public function update(Request $request)
    {
        $d = $request->validate([
            'company_name' => ['nullable', 'string', 'max:80'], 'vehicle_name' => ['nullable', 'string', 'max:80'],
            'support_email' => ['nullable', 'email', 'max:190'], 'sender_name' => ['nullable', 'string', 'max:80'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'member_prefix' => ['required', 'alpha_num', 'max:6'], 'member_digits' => ['required', 'integer', 'between:3,10'],
            'member_next' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:1'], 'public_price' => ['required', 'numeric', 'min:1'],
            'deposit' => ['required', 'numeric', 'min:1', 'lte:price'], 'balance_days' => ['required', 'integer', 'between:1,60'],
            'wire_details' => ['nullable', 'string', 'max:2000'],
        ]);
        foreach (self::TEXT as $k) {
            Setting::set($k, (string) ($d[$k] ?? ''));
        }
        Setting::set('member_prefix', strtoupper($d['member_prefix']));
        Setting::set('member_digits', $d['member_digits']);
        Setting::set('member_next', $d['member_next']);
        Setting::set('price_cents', (int) round($d['price'] * 100));
        Setting::set('public_price_cents', (int) round($d['public_price'] * 100));
        Setting::set('deposit_cents', (int) round($d['deposit'] * 100));
        Setting::set('balance_days', $d['balance_days']);
        Setting::set('wire_details', (string) ($d['wire_details'] ?? ''));
        Setting::put('country_check', $request->boolean('country_check'));
        Audit::record('settings.updated', null, null, ['keys' => array_keys($d)], $request->user()->id);

        return back()->with('toast', 'Settings saved.');
    }
}
