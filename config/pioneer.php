<?php

return [
    // Default prices in cents. Admin settings override these.
    'price_cents' => 50_000_000,
    'public_price_cents' => 150_000_000,
    'deposit_cents' => 10_000_000,
    'balance_days' => 10,
    'member_prefix' => 'PA',
    'member_digits' => 6,
    'max_failed' => 5,          // wrong passwords before the account locks
    'code_after_failed' => 3,   // wrong passwords before an emailed code is needed
    'lock_minutes' => 15,
    'code_minutes' => 10,
    'country_check' => true,
    'footer' => '[Company Name] is an independent portfolio project and is not a licensed financial institution. No deposits are accepted and no accounts are insured.',
];
