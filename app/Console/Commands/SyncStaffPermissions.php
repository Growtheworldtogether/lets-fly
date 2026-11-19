<?php

namespace App\Console\Commands;

use App\Services\StaffDirectory;
use Illuminate\Console\Command;

class SyncStaffPermissions extends Command
{
    protected $signature = 'admin:sync';
    protected $description = 'Write the staff title presets from config/admin.php to the permission tables';

    public function handle(): int
    {
        StaffDirectory::sync();
        $this->info('Staff permissions synced.');

        return self::SUCCESS;
    }
}
