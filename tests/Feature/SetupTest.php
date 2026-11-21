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
