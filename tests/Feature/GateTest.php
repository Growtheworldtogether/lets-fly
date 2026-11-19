<?php

use App\Models\User;
use App\Services\InviteService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->artisan('migrate:fresh');
    \App\Services\StaffDirectory::sync();
});

it('shows only the code page and hides everything else', function () {
    $this->get('/')->assertOk()->assertSee('Enter your invite code')->assertDontSee('Sign in</button>', false);
    $this->get('/sign-in')->assertNotFound();
    $this->post('/sign-in', ['password' => 'x'])->assertNotFound();
    $this->get('/dashboard')->assertRedirect();
});

it('rejects a wrong code', function () {
    $this->post('/enter', ['code' => 'AAAA-BBBB-CCCC-DDDD'])->assertRedirect()->assertSessionHas('error');
    $this->get('/sign-in')->assertNotFound();
});

it('claims an invite with its preset name, then signs in with the same code', function () {
    [$invite, $code] = app(InviteService::class)->create('Ada Lovelace', 'ada@example.com');
    $this->post('/enter', ['code' => $code])->assertRedirect('/sign-in');
    $this->get('/sign-in')->assertSee('Ada Lovelace')->assertSee('Create account');
    $this->post('/sign-in/claim', ['password' => 'Sup3rsecret!!', 'password_confirmation' => 'Sup3rsecret!!'])->assertRedirect('/dashboard');
    $user = User::where('email', 'ada@example.com')->first();
    expect($user->name)->toBe('Ada Lovelace')->and($user->member_number)->toBe('PA-000001')->and($invite->fresh()->status)->toBe('claimed');

    $this->post('/sign-out');
    $this->post('/enter', ['code' => strtolower($code)])->assertRedirect('/sign-in');
    $this->get('/sign-in')->assertSee('Welcome back')->assertDontSee('Create account');
    $this->post('/sign-in', ['password' => 'wrong'])->assertSessionHas('error');
    $this->post('/sign-in', ['password' => 'Sup3rsecret!!'])->assertRedirect('/dashboard');
    $this->get('/dashboard')->assertOk()->assertSee('PA-000001');
});

it('needs an emailed code after three wrong passwords and locks after five', function () {
    Mail::fake();
    [$invite, $code] = app(InviteService::class)->create('Bo', 'bo@example.com');
    $this->post('/enter', ['code' => $code]);
    $this->post('/sign-in/claim', ['password' => 'Sup3rsecret!!', 'password_confirmation' => 'Sup3rsecret!!']);
    $this->post('/sign-out');
    $this->post('/enter', ['code' => $code]);
    foreach (range(1, 3) as $i) {
        $this->post('/sign-in', ['password' => 'nope']);
    }
    $this->post('/sign-in', ['password' => 'Sup3rsecret!!'])->assertRedirect('/sign-in');
    $this->get('/sign-in')->assertSee('emailed');
    $this->get('/dashboard')->assertRedirect();
});

it('does not ask admins for an emailed code and keeps the member number off staff', function () {
    [$invite, $code] = app(InviteService::class)->create('Root', 'root@example.com', 'admin');
    $this->post('/enter', ['code' => $code]);
    $this->post('/sign-in/claim', ['password' => 'Sup3rsecret!!', 'password_confirmation' => 'Sup3rsecret!!'])->assertRedirect('/admin');
    $root = User::where('email', 'root@example.com')->first();
    expect($root->member_number)->toBeNull()->and($root->hasRole('Super Admin'))->toBeTrue();
    $this->get('/admin')->assertOk();
});

it('gives the next member number from the admin prefix setting', function () {
    \App\Models\Setting::set('member_prefix', 'ab');
    \App\Models\Setting::set('member_digits', 4);
    expect(app(InviteService::class)->nextMemberNumber())->toBe('AB-0001');
});

it('creates an admin invite from the command and can reissue a lost code', function () {
    $this->artisan('invite:admin', ['name' => 'Root User', 'email' => 'root@example.com'])->assertSuccessful();
    $invite = \App\Models\Invite::where('email', 'root@example.com')->first();
    expect($invite->kind)->toBe('admin');
    $old = $invite->code_lookup;
    $this->artisan('invite:admin', ['name' => 'Root User', 'email' => 'root@example.com'])->assertSuccessful();
    expect($invite->fresh()->code_lookup)->not->toBe($old);
});
