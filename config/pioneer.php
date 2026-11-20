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
    'footer' => 'Deposits are held in escrow by an independent escrow agent until allocation milestones are confirmed. Specifications, timelines and delivery dates are targets and may change. [Company Name] is not a bank, and funds held in escrow are not bank deposits or insured accounts.',
];
