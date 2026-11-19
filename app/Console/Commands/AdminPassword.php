<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AdminPassword extends Command
{
    protected $signature = 'admin:password {email : The admin\'s email address}';

    protected $description = 'Set a new password for an admin account (admins cannot reset by email).';

    public function handle(): int
    {
        $user = User::where('role', 'admin')->where('email', strtolower($this->argument('email')))->first();
        if (! $user) {
            $this->error('No admin has that email.');

            return self::FAILURE;
        }
        $pw = $this->secret('New password (at least 10 characters, letters and numbers)');
        if (strlen((string) $pw) < 10 || ! preg_match('/[A-Za-z]/', $pw) || ! preg_match('/\d/', $pw)) {
            $this->error('That password is too weak.');

            return self::FAILURE;
        }
        $user->forceFill(['password' => $pw, 'failed_logins' => 0, 'locked_until' => null])->save();
        $this->info('Password changed for '.$user->email.'.');

        return self::SUCCESS;
    }
}
