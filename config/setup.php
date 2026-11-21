<?php

return [
    // Written when setup finishes. While it is missing and no account exists, every page leads to /setup.
    'lock' => storage_path('app/installed.lock'),
    // The owner key: an 8-character code the person must read from this file on the server to prove they own it.
    'key_file' => storage_path('app/install-key.txt'),
    // Admin recovery without a command line: while this file exists (and is under an hour old), /recover works.
    'recovery_file' => storage_path('app/recovery.txt'),
    // Tests treat the site as installed unless a test turns this on.
    'check_in_tests' => false,
];
