<?php

use App\Models\User;
use App\Support\Installer;

beforeEach(function () {
    $this->artisan('migrate:fresh');
    $this->lock = sys_get_temp_dir().'/pioneer-lock-'.uniqid();
    $this->keyFile = sys_get_temp_dir().'/pioneer-key-'.uniqid();
    config(['setup.check_in_tests' => true, 'setup.lock' => $this->lock, 'setup.key_file' => $this->keyFile]);
    Installer::forget();
});

afterEach(function () {
    @unlink($this->lock);
    @unlink($this->keyFile);
    Installer::forget();
});

it('sends every page to setup until the site is installed', function () {
    $this->get('/')->assertRedirect('/setup');
    $this->get('/dashboard')->assertRedirect('/setup');
    $this->get('/setup')->assertOk()->assertSee('Confirm you own this site');
});

it('opens steps in order only', function () {
    $this->get('/setup/review')->assertRedirect('/setup/key');
    $this->post('/setup/database', ['driver' => 'sqlite'])->assertForbidden();
});

it('needs the owner key from anywhere but the server itself', function () {
    $remote = ['REMOTE_ADDR' => '203.0.113.9'];
    $this->withServerVariables($remote)->get('/setup/key')->assertOk()->assertSee('install-key.txt');
    $key = Installer::key();
    $this->withServerVariables($remote)->post('/setup/key', ['key' => 'WRONGKEY'])->assertSessionHasErrors('key');
    $this->withServerVariables($remote)->get('/setup/check')->assertRedirect('/setup/key');
    $this->withServerVariables($remote)->post('/setup/key', ['key' => strtolower($key)])->assertRedirect('/setup/check');
});

it('locks out a guesser after five wrong keys', function () {
    $remote = ['REMOTE_ADDR' => '203.0.113.10'];
    Installer::key();
    foreach (range(1, 5) as $i) {
        $this->withServerVariables($remote)->post('/setup/key', ['key' => 'WRONGKEY'])->assertSessionHasErrors('key');
    }
    $this->withServerVariables($remote)->post('/setup/key', ['key' => Installer::key()])->assertSessionHasErrors('key');
});

it('skips the key on the server itself but not behind a proxy', function () {
    expect(Installer::keyRequired(request()->duplicate(server: ['REMOTE_ADDR' => '127.0.0.1'])))->toBeFalse();
    $proxied = request()->duplicate(server: ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.9']);
    expect(Installer::keyRequired($proxied))->toBeTrue();
});

it('is gone once installed, and a site that already has accounts counts as installed', function () {
    User::create(['name' => 'Root', 'email' => 'r@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'admin', 'email_verified_at' => now()]);
    $this->get('/setup')->assertNotFound();
    $this->get('/setup/key')->assertNotFound();
    $this->post('/setup/key', [])->assertNotFound();
    expect(is_file($this->lock))->toBeTrue();
});

it('validates the site address and admin details', function () {
    $this->post('/setup/key')->assertRedirect('/setup/check');
    $this->post('/setup/check')->assertRedirect('/setup/site');
    $this->post('/setup/site', ['url' => 'not a url', 'tz' => 'Mars/Base'])->assertSessionHasErrors(['url', 'tz']);
    $this->post('/setup/site', ['url' => 'https://example.com/', 'tz' => 'America/Chicago'])->assertRedirect('/setup/database');
    $this->post('/setup/database', ['driver' => 'sqlite'])->assertRedirect('/setup/email');
    $this->post('/setup/email', ['mode' => 'later'])->assertRedirect('/setup/admin');
    $this->post('/setup/admin', ['name' => 'Al', 'email' => 'nope'])->assertSessionHasErrors(['name', 'email']);
    $this->post('/setup/admin', ['name' => 'Ada Admin', 'email' => 'ada@example.com'])->assertRedirect('/setup/review');
    $this->get('/setup/review')->assertOk()->assertSee('ada@example.com')->assertSee('America/Chicago');
});

it('writes settings into an env file, quoting values that need it', function () {
    $path = sys_get_temp_dir().'/pioneer-env-'.uniqid();
    file_put_contents($path, "APP_NAME=Old\n#APP_URL=x\nKEEP=1\n");
    Installer::writeEnv(['APP_NAME' => 'My Company #1', 'APP_URL' => 'https://example.com', 'NEW_KEY' => 'a$b'], $path);
    $env = file_get_contents($path);
    unlink($path);
    expect($env)->toContain('APP_NAME="My Company #1"')->toContain('APP_URL=https://example.com')->toContain('KEEP=1')->toContain('NEW_KEY="a\\$b"')->not->toContain('#APP_URL');
});

it('keeps admin recovery switched off unless the recovery file exists', function () {
    $file = sys_get_temp_dir().'/pioneer-recovery-'.uniqid();
    config(['setup.recovery_file' => $file, 'setup.check_in_tests' => false]);
    $this->get('/recover')->assertNotFound();
    $this->post('/recover', ['secret' => 'whatever-long-secret'])->assertNotFound();
    file_put_contents($file, 'short');
    $this->get('/recover')->assertNotFound();
    file_put_contents($file, 'a-long-secret-text');
    touch($file, time() - 7200);
    $this->get('/recover')->assertNotFound();
    unlink($file);
});

it('issues a new admin invite or password with the recovery file, once', function () {
    config(['setup.check_in_tests' => false]);
    $file = sys_get_temp_dir().'/pioneer-recovery-'.uniqid();
    config(['setup.recovery_file' => $file]);
    $admin = User::create(['name' => 'Root', 'email' => 'root@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'admin', 'email_verified_at' => now()]);
    $admin->forceFill(['failed_logins' => 5, 'locked_until' => now()->addHour()])->save();
    file_put_contents($file, 'a-long-secret-text');

    $this->get('/recover')->assertOk()->assertSee('Administrator recovery');
    $this->post('/recover', ['secret' => 'wrong-secret-text', 'action' => 'invite', 'email' => 'root@example.com'])->assertSessionHasErrors('secret');
    expect(is_file($file))->toBeTrue();

    $this->post('/recover', ['secret' => 'a-long-secret-text', 'action' => 'password', 'email' => 'nobody@example.com', 'password' => 'Newpass12345'])->assertSessionHasErrors('email');
    $this->post('/recover', ['secret' => 'a-long-secret-text', 'action' => 'password', 'email' => 'root@example.com', 'password' => 'weak'])->assertSessionHasErrors('password');
    $this->post('/recover', ['secret' => 'a-long-secret-text', 'action' => 'password', 'email' => 'root@example.com', 'password' => 'Newpass12345'])->assertOk()->assertSee('Password changed');
    expect(\Illuminate\Support\Facades\Hash::check('Newpass12345', $admin->fresh()->password))->toBeTrue();
    expect($admin->fresh()->locked_until)->toBeNull();
    expect(is_file($file))->toBeFalse();
    $this->get('/recover')->assertNotFound();

    file_put_contents($file, 'a-long-secret-text');
    $res = $this->post('/recover', ['secret' => 'a-long-secret-text', 'action' => 'invite', 'email' => 'root@example.com', 'name' => 'Root'])->assertOk()->assertSee('New invite code');
    expect(\App\Models\Invite::where('kind', 'admin')->where('email', 'root@example.com')->exists())->toBeTrue();
    expect(is_file($file))->toBeFalse();
    expect(\App\Models\AuditLog::whereIn('action', ['recovery.password', 'recovery.invite'])->count())->toBe(2);
});

it('switches recovery off after five wrong tries', function () {
    config(['setup.check_in_tests' => false]);
    $file = sys_get_temp_dir().'/pioneer-recovery-'.uniqid();
    config(['setup.recovery_file' => $file]);
    file_put_contents($file, 'a-long-secret-text');
    foreach (range(1, 5) as $i) {
        $this->post('/recover', ['secret' => 'wrong-secret-text', 'action' => 'invite', 'email' => 'a@example.com'])->assertSessionHasErrors('secret');
    }
    $this->post('/recover', ['secret' => 'a-long-secret-text', 'action' => 'invite', 'email' => 'a@example.com'])->assertSessionHasErrors('secret');
    expect(is_file($file))->toBeFalse();
});

it('starts the wrong-try count again when the recovery file is made again', function () {
    config(['setup.check_in_tests' => false]);
    $file = sys_get_temp_dir().'/pioneer-recovery-'.uniqid();
    config(['setup.recovery_file' => $file]);
    file_put_contents($file, 'a-long-secret-text');
    touch($file, time() - 100);
    foreach (range(1, 5) as $i) {
        $this->post('/recover', ['secret' => 'wrong-secret-text', 'action' => 'invite', 'email' => 'a@example.com'])->assertSessionHasErrors('secret');
    }
    $this->post('/recover', ['secret' => 'a-long-secret-text', 'action' => 'invite', 'email' => 'a@example.com'])->assertSessionHasErrors('secret');
    expect(is_file($file))->toBeFalse();

    file_put_contents($file, 'a-new-long-secret');
    $this->post('/recover', ['secret' => 'a-new-long-secret', 'action' => 'invite', 'email' => 'a@example.com', 'name' => 'A'])->assertOk()->assertSee('New invite code');
});
