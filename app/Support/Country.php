<?php

namespace App\Support;

use Illuminate\Http\Request;

/** Two-letter country of a request. Uses the CDN header when present, else a local GeoLite2 Country file if installed. */
final class Country
{
    public static function of(Request $request): ?string
    {
        foreach (['CF-IPCountry', 'X-Country-Code'] as $h) {
            $v = strtoupper((string) $request->header($h));
            if (preg_match('/^[A-Z]{2}$/', $v) && $v !== 'XX' && $v !== 'T1') {
                return $v;
            }
        }
        $db = storage_path('app/GeoLite2-Country.mmdb');
        if (is_file($db) && class_exists(\GeoIp2\Database\Reader::class)) {
            try {
                return (new \GeoIp2\Database\Reader($db))->country($request->ip())->country->isoCode;
            } catch (\Throwable) {
            }
        }

        return null;
    }
}
