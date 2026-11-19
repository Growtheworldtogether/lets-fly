<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

/** The company and vehicle names come from admin settings. Until they are set, bracketed placeholders show. */
final class Brand
{
    public const DEFAULTS = [
        'name' => '[Company Name]',
        'vehicle' => '[Vehicle Name]',
        'support_email' => 'support@example.com',
        'sender_name' => '[Company Name]',
    ];

    public static function all(): array
    {
        $out = self::DEFAULTS;
        try {
            if (Schema::hasTable('settings')) {
                foreach (['name' => 'company_name', 'vehicle' => 'vehicle_name', 'support_email' => 'support_email', 'sender_name' => 'sender_name'] as $k => $key) {
                    $v = Setting::find($key)?->value;
                    if ($v !== null && $v !== '') {
                        $out[$k] = $v;
                    }
                }
                $out['footer'] = str_replace('[Company Name]', $out['name'], Setting::value('footer_text', config('pioneer.footer')));
            }
        } catch (\Throwable) {
        }
        $out['footer'] ??= str_replace('[Company Name]', $out['name'], config('pioneer.footer'));

        return $out;
    }

    public static function get(string $key): mixed
    {
        return self::all()[$key] ?? null;
    }
}
