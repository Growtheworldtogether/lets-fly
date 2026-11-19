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

function adminSession($t, string $title = 'Super Admin') {
    $u = User::create(['name' => 'Root', 'email' => 'r'.uniqid().'@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'admin', 'email_verified_at' => now()]);
    \App\Services\StaffDirectory::assign($u, $title);

    return $u;
}

it('lets an admin create, revoke and regenerate invites, and the code shows once', function () {
    $admin = adminSession($this);
    $this->actingAs($admin)->post('/admin/invites', ['name' => 'Cy Young', 'email' => 'cy@example.com', 'kind' => 'member'])->assertRedirect('/admin/invites');
    $code = session('new_code.code');
    expect($code)->toMatch('/^[A-Z2-9]{4}(-[A-Z2-9]{4}){3}$/');
    $this->get('/admin/invites')->assertSee('Cy Young')->assertSee(session('new_code.code') ?? 'x');
    $this->get('/admin/invites')->assertDontSee($code);
    $inv = \App\Models\Invite::first();
    $this->post("/admin/invites/{$inv->id}/regenerate");
    expect(app(InviteService::class)->find($code))->toBeNull();
    $new = session('new_code.code');
    expect(app(InviteService::class)->find($new))->not->toBeNull();
    $this->post("/admin/invites/{$inv->id}/revoke");
    expect(app(InviteService::class)->find($new))->toBeNull();
    $this->get('/admin/invites/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('keeps a Manager out of settings and admin invites but lets them run member invites', function () {
    $mgr = adminSession($this, 'Manager');
    $this->actingAs($mgr)->get('/admin/settings')->assertForbidden();
    $this->post('/admin/invites', ['name' => 'Dee', 'kind' => 'admin'])->assertForbidden();
    $this->post('/admin/invites', ['name' => 'Dee', 'kind' => 'member'])->assertRedirect();
    $this->get('/admin/funnel')->assertOk()->assertSee('Invite created');
});

it('saves settings and the new prefix applies to the next member', function () {
    $admin = adminSession($this);
    $this->actingAs($admin)->post('/admin/settings', [
        'company_name' => 'Skyward', 'vehicle_name' => 'Aero One', 'member_prefix' => 'sk', 'member_digits' => 5, 'member_next' => 7,
        'price' => 500000, 'public_price' => 1500000, 'deposit' => 100000, 'balance_days' => 10, 'country_check' => '1',
    ])->assertSessionHasNoErrors();
    expect(brand('name'))->toBe('Skyward')->and(app(InviteService::class)->nextMemberNumber())->toBe('SK-00007');
    $this->get('/admin/settings')->assertOk()->assertSee('Skyward');
});

it('logs an admin in as a member and back out, and records it', function () {
    $admin = adminSession($this);
    $m = User::create(['name' => 'Eve', 'email' => 'eve@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000009']);
    $this->actingAs($admin)->post("/admin/members/{$m->id}/impersonate")->assertRedirect('/dashboard');
    $this->get('/dashboard')->assertSee('PA-000009')->assertSee('Signed in as Eve');
    $this->get('/admin/invites')->assertForbidden();
    $this->post('/admin/exit-member')->assertRedirect('/admin/members');
    $this->get('/admin')->assertOk();
    expect(\App\Models\AuditLog::whereIn('action', ['impersonation.started', 'impersonation.ended'])->count())->toBe(2);
});

it('shows members the vehicle pages with placeholders, hides inactive sections, and keeps outsiders out', function () {
    $this->get('/vehicle')->assertRedirect();
    $m = User::create(['name' => 'Fay', 'email' => 'fay@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000002']);
    $this->actingAs($m)->get('/vehicle')->assertOk()->assertSee('Pilot training')->assertSee('Image placeholder');
    $this->get('/vehicle/flight')->assertOk()->assertSee('Flight and handling')->assertSee('Performance');
    \App\Models\PageSection::where('slug', 'flight')->update(['active' => false]);
    $this->get('/vehicle/flight')->assertNotFound();
    $this->get('/vehicle/nope')->assertNotFound();
});

it('lets staff upload a picture and a video, plays the video to members only, and takes a YouTube link', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $admin = adminSession($this);
    $img = \Illuminate\Http\UploadedFile::fake()->image('car.jpg', 800, 450);
    $mp4 = \Illuminate\Http\UploadedFile::fake()->createWithContent('clip.mp4', "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom\x00\x00\x00\x08free");
    $this->actingAs($admin)->get('/admin/content')->assertOk()->assertSee('Vehicle pages');
    $this->post('/admin/content/exterior', ['title' => 'Exterior', 'sort' => 10, 'active' => 1, 'image' => $img, 'video' => $mp4])->assertSessionHasNoErrors();
    $s = \App\Models\PageSection::where('slug', 'exterior')->first();
    expect($s->image_id)->not->toBeNull()->and($s->video_path)->toStartWith('private/videos/');
    \Illuminate\Support\Facades\Storage::disk('local')->assertExists($s->video_path);
    $this->get('/vehicle/exterior')->assertOk()->assertSee('/vehicle/exterior/video', false);
    $this->post('/admin/content/exterior', ['title' => 'Exterior', 'sort' => 10, 'active' => 1, 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertSessionHasNoErrors();
    $s->refresh();
    expect($s->video_path)->toBeNull()->and($s->embedUrl())->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
    $this->get('/vehicle/exterior')->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
});

it('refuses a video that is not MP4 or WebM and a non-https link', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $admin = adminSession($this);
    $bad = \Illuminate\Http\UploadedFile::fake()->createWithContent('x.mp4', 'just text pretending');
    $this->actingAs($admin)->get('/admin/content');
    $this->post('/admin/content/power', ['title' => 'Power', 'sort' => 1, 'video' => $bad])->assertSessionHasErrors('video');
    $this->post('/admin/content/power', ['title' => 'Power', 'sort' => 1, 'video_url' => 'http://example.com/a.mp4'])->assertSessionHasErrors('video_url');
    $mgr = adminSession($this, 'Manager');
    $this->actingAs($mgr)->get('/admin/content')->assertOk();
});

function memberWithInvite(): User {
    [$invite] = app(InviteService::class)->create('Gus', 'gus@example.com');
    $u = User::create(['name' => 'Gus', 'email' => 'gus@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000010']);
    $invite->update(['user_id' => $u->id, 'status' => 'claimed']);

    return $u;
}

it('lets a member accept the agreement and get a deposit invoice with the right amounts', function () {
    Mail::fake();
    \App\Models\Setting::set('wire_details', "Bank: Example Bank\nAccount: 12345");
    $u = memberWithInvite();
    $this->actingAs($u)->get('/dashboard')->assertSee('Reserve my allocation')->assertSee('$500,000.00');
    $this->get('/reserve')->assertOk()->assertSee('$100,000.00')->assertSee('$400,000.00')->assertSee('10 days');
    $this->post('/reserve', [])->assertSessionHasErrors('agree');
    $this->post('/reserve', ['agree' => 1])->assertRedirect('/dashboard');
    $r = \App\Models\Reservation::first();
    expect($r->deposit_cents)->toBe(10_000_000)->and($r->balance_cents)->toBe(40_000_000)->and($r->agreement_version)->toBe(1)->and($r->status)->toBe('deposit_invoiced')
        ->and($r->invoices)->toHaveCount(1)->and($r->invoices[0]->number)->toStartWith('INV-'.now()->format('Y').'-');
    $this->get('/dashboard')->assertSee($r->reference)->assertSee('Download PDF')->assertSee('Account: 12345');
    $this->get('/reserve')->assertRedirect('/dashboard');
    $this->post('/reserve', ['agree' => 1]);
    expect(\App\Models\Reservation::count())->toBe(1);
    expect(\App\Models\InviteEvent::whereIn('type', ['agreed', 'invoiced'])->count())->toBe(2);
});

it('sends the invoice email without links and downloads a PDF only for its owner or staff', function () {
    $u = memberWithInvite();
    $this->actingAs($u)->post('/reserve', ['agree' => 1]);
    $inv = \App\Models\Invoice::first();
    expect($inv->emailed_at)->not->toBeNull();
    $html = view('mail.invoice', ['invoice' => $inv, 'r' => $inv->reservation, 'user' => $u, 'wire' => ''])->render();
    expect($html)->toContain('$100,000.00')->toContain('PA-000010')->not->toContain('http://')->not->toContain('https://');
    $res = $this->get("/invoices/{$inv->id}/pdf");
    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('pdf');
    expect(substr($res->getContent(), 0, 5))->toBe('%PDF-');
    $other = User::create(['name' => 'Hal', 'email' => 'hal@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member']);
    $this->actingAs($other)->get("/invoices/{$inv->id}/pdf")->assertNotFound();
    $this->actingAs(adminSession($this))->get("/invoices/{$inv->id}/pdf")->assertOk();
});

it('does not let an admin accept the agreement while acting as a member', function () {
    $u = memberWithInvite();
    $admin = adminSession($this);
    $this->actingAs($admin)->post("/admin/members/{$u->id}/impersonate");
    $this->post('/reserve', ['agree' => 1])->assertForbidden();
    expect(\App\Models\Reservation::count())->toBe(0);
});

it('publishes a new agreement version without changing what earlier members accepted', function () {
    Mail::fake();
    $u = memberWithInvite();
    $this->actingAs($u)->post('/reserve', ['agree' => 1]);
    $admin = adminSession($this);
    $this->actingAs($admin)->get('/admin/agreement')->assertOk()->assertSee('Version 1 is live');
    $this->post('/admin/agreement', ['body' => str_repeat('New terms for the allocation. ', 5)])->assertSessionHasNoErrors();
    expect(\App\Models\AgreementVersion::current()->version)->toBe(2)->and(\App\Models\Reservation::first()->agreement_version)->toBe(1);
    $this->get('/admin/reservations')->assertOk()->assertSee(\App\Models\Reservation::first()->reference);
    $mgr = adminSession($this, 'Manager');
    $this->actingAs($mgr)->get('/admin/agreement')->assertForbidden();
    $this->get('/admin/reservations')->assertOk();
});

it('shows when the balance is due on the dashboard', function () {
    Mail::fake();
    $u = memberWithInvite();
    $this->actingAs($u)->post('/reserve', ['agree' => 1]);
    $this->get('/dashboard')->assertSee('due 10 days after your deposit is verified');
    \App\Models\Reservation::first()->update(['balance_due_at' => now()->addDays(10)]);
    $this->get('/dashboard')->assertSee('due '.now()->addDays(10)->timezone('America/New_York')->format('M j, Y'));
});
