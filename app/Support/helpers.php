<?php

use App\Services\Brand;

if (! function_exists('brand')) {
    /** Company identity: brand('name'), brand('vehicle'), brand('footer'), or brand() for everything. */
    function brand(?string $key = null): mixed
    {
        return $key === null ? Brand::all() : Brand::get($key);
    }
}

if (! function_exists('brand_logo')) {
    /** URL of the uploaded site logo ('site') or vehicle-name logo ('vehicle'), or null when none has been uploaded. */
    function brand_logo(string $role): ?string
    {
        return \App\Services\Logos::url($role);
    }
}
