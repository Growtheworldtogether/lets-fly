<?php

// Staff titles and what each may do. `php artisan admin:sync` writes these to the permission tables.
return [
    'permissions' => [
        'members.manage' => 'Look up members and manage invites',
        'payments.verify' => 'Verify deposits and payments',
        'content.edit' => 'Edit the vehicle pages and videos',
        'audit.view' => 'View the audit log',
        'impersonate' => 'Log in as member',
        'admin.manage' => 'Manage staff, settings and updates',
    ],
    'titles' => [
        'Manager' => ['members.manage', 'payments.verify', 'content.edit', 'audit.view'],
        'Super Admin' => ['*'],
    ],
    'idle_timeout_minutes' => 30,
    'impersonation_minutes' => 30,
];
