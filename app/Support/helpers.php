<?php

use App\Services\Brand;

if (! function_exists('brand')) {
    /** Company identity: brand('name'), brand('vehicle'), brand('footer'), or brand() for everything. */
    function brand(?string $key = null): mixed
    {
        return $key === null ? Brand::all() : Brand::get($key);
    }
}
