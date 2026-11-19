<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\StaffDirectory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email} {--name= : Full name} {--title=Super Admin : Teller, Manager, Compliance, Support Agent or Super Admin}';

    protected $description = 'Create a staff account on a server where demo data is not seeded';

    public function handle(): int
    {
        $title = (string) $this->option('title');
        if (! array_key_exists($title, config('admin.titles'))) {
            $this->error('Choose one of: '.implode(', ', array_keys(config('admin.titles'))).'.');

            return self::FAILURE;
        }
        $name = trim((string) ($this->option('name') ?: $this->ask('Full name')));
        $password = (string) $this->secret('Password (12 or more characters)');
        $v = Validator::make(['email' => $this->argument('email'), 'name' => $name, 'password' => $password], [
            'email' => ['required', 'email', 'unique:users,email'], 'name' => ['required', 'string', 'min:3', 'max:120'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()],
        ]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }

        [$first, $last] = array_pad(explode(' ', $name, 2), 2, '');
        $user = User::create(['name' => $name, 'first_name' => $first, 'last_name' => $last, 'email' => $this->argument('email'), 'password' => $password]);
        $user->forceFill(['kyc_status' => 'verified', 'email_verified_at' => now()])->save();
        StaffDirectory::assign($user, $title);
        $this->info("Created {$title} {$user->email}. They can turn on two-step verification from the Security page.");

        return self::SUCCESS;
    }
}
