<?php

namespace App\Console\Commands;

use App\Services\InviteService;
use App\Services\StaffDirectory;
use Illuminate\Console\Command;

class CreateAdminInvite extends Command
{
    protected $signature = 'invite:admin {name : The admin\'s full name} {email : Their email address}';

    protected $description = 'Create an admin invite code. The code is shown once. Run again to issue a fresh one if it is lost.';

    public function handle(InviteService $invites): int
    {
        StaffDirectory::sync();
        $email = strtolower($this->argument('email'));
        $existing = \App\Models\Invite::where('kind', 'admin')->where('email', $email)->first();
        if ($existing) {
            $code = $invites->regenerate($existing);
            $this->info('A new code replaced the old one for '.$email.'.');
        } else {
            [, $code] = $invites->create($this->argument('name'), $email, 'admin');
        }
        $this->newLine();
        $this->line('Admin invite code (shown once, keep it safe):');
        $this->line('  '.$code);
        $this->newLine();

        return self::SUCCESS;
    }
}
