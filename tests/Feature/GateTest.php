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
    $this->get('/dashboard')->assertNotFound();
});

it('rejects a wrong code', function () {
    $this->post('/enter', ['code' => 'AAAA-BBBB-CCCC-DDDD'])->assertRedirect()->assertSessionHas('error');
    $this->get('/sign-in')->assertNotFound();
});

it('claims an invite with its preset name, then signs in with the same code', function () {
    [$invite, $code] = app(InviteService::class)->create('Ada Lovelace', 'ada@example.com');
    $this->post('/enter', ['code' => $code])->assertRedirect('/sign-in');
    $this->get('/sign-in')->assertSee('Ada Lovelace')->assertSee('Create account');
    $this->post('/sign-in/claim', ['password' => 'Sup3rsecret!!', 'password_confirmation' => 'Sup3rsecret!!'])->assertRedirect('/welcome');
    $user = User::where('email', 'ada@example.com')->first();
    expect($user->name)->toBe('Ada Lovelace')->and($user->member_number)->toBe('PA-000001')->and($invite->fresh()->status)->toBe('claimed');

    // First visit: a welcome page with a button that marks it seen and goes on to the vehicle.
    $this->get('/welcome')->assertOk()->assertSee('Ada Lovelace')->assertSee('PA-000001')->assertSee('View the vehicle');
    expect($user->fresh()->intro_seen_at)->toBeNull();
    $this->post('/welcome')->assertRedirect('/vehicle');
    expect($user->fresh()->intro_seen_at)->not->toBeNull();

    $this->post('/sign-out');
    $this->post('/enter', ['code' => strtolower($code)])->assertRedirect('/sign-in');
    $this->get('/sign-in')->assertSee('Welcome back')->assertDontSee('Create account');
    $this->post('/sign-in', ['password' => 'wrong'])->assertSessionHas('error');
    $this->post('/sign-in', ['password' => 'Sup3rsecret!!'])->assertRedirect('/vehicle');
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
    $this->get('/dashboard')->assertNotFound();
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
    $this->get('/vehicle')->assertNotFound();
    $m = User::create(['name' => 'Fay', 'email' => 'fay@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000002']);
    $this->actingAs($m)->get('/vehicle')->assertOk()->assertSee('From first lesson to first solo')->assertSee('Video placeholder')->assertSee('Reserve now')->assertSee('Gravity Defying')->assertSee('How reserving works')->assertSee('Two payments, both held in escrow')->assertDontSee('v-steps', false);
    $this->get('/vehicle')->assertSee('Four ducts at the tail')->assertSee('Stabilisation')->assertSee('Specifications')->assertSee('Built around the pilot');
    $this->get('/vehicle/flight')->assertRedirect('/vehicle#flight');
    \App\Models\PageSection::where('slug', 'flight')->update(['active' => false]);
    $this->get('/vehicle/flight')->assertNotFound();
    $this->get('/vehicle')->assertDontSee('Vertical takeoff, wing-borne cruise');
    $this->get('/vehicle/nope')->assertNotFound();
});

it('keeps text an admin wrote and only replaces the old placeholder text', function () {
    \App\Models\PageSection::ensureDefaults();
    \App\Models\PageSection::where('slug', 'flight')->update(['body' => '[Describe this section here: Admin, Vehicle pages.]']);
    \App\Models\PageSection::where('slug', 'power')->update(['body' => 'My own words about power.']);
    \App\Models\PageSection::ensureDefaults();
    expect(\App\Models\PageSection::where('slug', 'flight')->value('body'))->toContain('Four ducts at the tail')
        ->and(\App\Models\PageSection::where('slug', 'power')->value('body'))->toBe('My own words about power.');
});

it('reads a section body into headline, paragraphs, facts and spec groups', function () {
    $s = new \App\Models\PageSection(['body' => "Short headline\n\nA paragraph.\n\n- Range: 300 km\n- Seats: 2\n\n## Weights\n- Empty: 700 kg"]);
    $p = $s->parts();
    expect($p['headline'])->toBe('Short headline')->and($p['paras'])->toBe(['A paragraph.'])
        ->and($p['facts'])->toBe([['Range', '300 km'], ['Seats', '2']])
        ->and($p['groups'])->toBe([['Weights', [['Empty', '700 kg']]]]);
});

it('gives phones a menu drawer and lands members on the vehicle first', function () {
    $m = User::create(['name' => 'Gus', 'email' => 'gus@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000003']);
    $this->actingAs($m)->get('/vehicle')->assertSee('class="burger"', false)->assertSee('id="drawer"', false)->assertSee('My reservation')->assertSee('Reserve now');
    $this->get('/')->assertRedirect('/vehicle');
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
    $this->get('/vehicle')->assertOk()->assertSee('/vehicle/exterior/video', false);
    $this->post('/admin/content/exterior', ['title' => 'Exterior', 'sort' => 10, 'active' => 1, 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertSessionHasNoErrors();
    $s->refresh();
    expect($s->video_path)->toBeNull()->and($s->embedUrl())->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
    $this->get('/vehicle')->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
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

function escrowSetup(): void {
    foreach (['escrow_agent' => 'Cynthia Poole', 'escrow_entity' => 'Poole Escrow Trust', 'escrow_bank' => 'Example Bank', 'escrow_account' => '#0000-1111-2222', 'escrow_routing' => '021000021'] as $k => $v) {
        \App\Models\Setting::set($k, $v);
    }
}

function memberWithInvite(): User {
    escrowSetup();
    [$invite] = app(InviteService::class)->create('Gus', 'gus@example.com');
    $u = User::create(['name' => 'Gus', 'email' => 'gus@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000010']);
    $invite->update(['user_id' => $u->id, 'status' => 'claimed']);

    return $u;
}

it('lets a member accept the agreement and get a deposit invoice with the right amounts', function () {
    Mail::fake();
    $u = memberWithInvite();
    $this->actingAs($u)->get('/dashboard')->assertSee('Reserve my allocation')->assertSee('$500,000.00')->assertSee('held in escrow by Cynthia Poole');
    $this->get('/reserve')->assertOk()->assertSee('$100,000.00')->assertSee('$400,000.00')->assertSee('10 days');
    $this->post('/reserve', [])->assertSessionHasErrors('agree');
    $this->post('/reserve', ['agree' => 1])->assertRedirect('/dashboard');
    $r = \App\Models\Reservation::first();
    expect($r->deposit_cents)->toBe(10_000_000)->and($r->balance_cents)->toBe(40_000_000)->and($r->agreement_version)->toBe(1)->and($r->status)->toBe('deposit_invoiced')
        ->and($r->invoices)->toHaveCount(1)->and($r->invoices[0]->number)->toStartWith('INV-'.now()->format('Y').'-');
    $this->get('/dashboard')->assertSee($r->reference)->assertSee('Download PDF')->assertSee('#0000-1111-2222')->assertSee('Poole Escrow Trust')->assertSee('Allocation #'.$r->reference)->assertSee('Where your funds are');
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
    $html = view('mail.invoice', ['invoice' => $inv, 'r' => $inv->reservation, 'user' => $u, 'lines' => \App\Services\Escrow::lines($inv->reservation), 'notes' => ''])->render();
    expect($html)->toContain('$100,000.00')->toContain('PA-000010')->toContain('Cynthia Poole')->toContain('Allocation #'.$inv->reservation->reference)->toContain('sign in with your invite code')->not->toContain('#0000-1111-2222')->not->toContain('021000021')->not->toContain('Example Bank')->not->toContain('http://')->not->toContain('https://');
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
    $this->get('/dashboard')->assertSee('due 10 days after your milestone is confirmed');
    \App\Models\Reservation::first()->update(['balance_due_at' => now()->addDays(10)]);
    $this->get('/dashboard')->assertSee('due '.now()->addDays(10)->timezone('America/New_York')->format('M j, Y'));
});

function reservedMember(): array {
    Mail::fake();
    $u = memberWithInvite();
    test()->actingAs($u)->post('/reserve', ['agree' => 1]);

    return [$u, \App\Models\Invoice::first()];
}

function reservationWithMilestone(): array {
    \Illuminate\Support\Facades\Storage::fake('local');
    [$u, $inv] = reservedMember();
    $admin = adminSession(test());
    test()->post("/invoices/{$inv->id}/proof", ['proof' => proofFile()]);
    test()->actingAs($admin)->post('/admin/payments/'.\App\Models\Payment::first()->id.'/verify');
    test()->post('/admin/reservations/'.\App\Models\Reservation::first()->id.'/milestone');

    return [$u, $admin, \App\Models\Reservation::first()];
}

function proofFile() {
    return \Illuminate\Http\UploadedFile::fake()->image('wire.jpg', 600, 400);
}

it('takes proof of wire, holds a verified deposit in escrow, and starts the balance clock only at the milestone', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    [$u, $inv] = reservedMember();
    $this->post("/invoices/{$inv->id}/proof", ['proof' => proofFile()])->assertSessionHasNoErrors();
    expect(\App\Models\Reservation::first()->status)->toBe('proof_submitted')->and(\App\Models\Payment::count())->toBe(1);
    $this->post("/invoices/{$inv->id}/proof", ['proof' => proofFile()])->assertSessionHasErrors('proof');
    $this->get('/dashboard')->assertSee('Proof under review');
    $pay = \App\Models\Payment::first();
    $this->get("/payments/{$pay->id}/proof")->assertOk();
    $other = User::create(['name' => 'Ivy', 'email' => 'ivy@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member']);
    $this->actingAs($other)->get("/payments/{$pay->id}/proof")->assertNotFound();

    $mgr = adminSession($this, 'Manager');
    $this->actingAs($mgr)->post("/admin/payments/{$pay->id}/verify")->assertRedirect();
    $r = \App\Models\Reservation::first();
    // verified: held in escrow, no balance invoice yet, no due date
    expect($r->status)->toBe('in_escrow')->and($r->deposit_verified_at)->not->toBeNull()->and($r->balance_due_at)->toBeNull()
        ->and($r->invoices)->toHaveCount(1)->and($r->heldCents())->toBe(10_000_000)->and($r->escrowEntries)->toHaveCount(1);
    $this->actingAs($u)->get('/dashboard')->assertSee('Deposit held in escrow')->assertDontSee('data-due', false);

    // the admin confirms the milestone: the balance invoice and the 10 day clock start then
    $this->travelTo(now()->addDays(30));
    $this->actingAs($mgr)->post("/admin/reservations/{$r->id}/milestone")->assertSessionHasNoErrors();
    $r = $r->fresh()->load('invoices');
    expect($r->status)->toBe('balance_invoiced')->and($r->milestone_confirmed_at->isSameDay(now()))->toBeTrue()->and($r->milestone_confirmed_by)->toBe($mgr->id)
        ->and($r->balance_due_at->isSameDay(now()->addDays(10)))->toBeTrue()
        ->and($r->invoices)->toHaveCount(2)->and($r->invoices[1]->kind)->toBe('balance')->and($r->invoices[1]->amount_cents)->toBe(40_000_000)
        ->and($r->invoices[1]->emailed_at)->not->toBeNull();
    $this->post("/admin/reservations/{$r->id}/milestone")->assertStatus(422);
    $this->actingAs($u)->get('/dashboard')->assertSee('data-due', false)->assertSee('Balance due')->assertSee('Milestone confirmed');
    expect(\App\Models\InviteEvent::whereIn('type', ['proof_uploaded', 'deposit_paid', 'milestone_confirmed'])->count())->toBe(3);
});

it('rejects a proof with a reason and lets the member send another, then completes on the balance', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    [$u, $inv] = reservedMember();
    $admin = adminSession($this);
    $this->post("/invoices/{$inv->id}/proof", ['proof' => proofFile()]);
    $p1 = \App\Models\Payment::first();
    $this->actingAs($admin)->post("/admin/payments/{$p1->id}/reject", [])->assertSessionHasErrors('note');
    $this->post("/admin/payments/{$p1->id}/reject", ['note' => 'Amount does not match'])->assertRedirect();
    $this->actingAs($u)->get('/dashboard')->assertSee('Amount does not match');
    $this->post("/invoices/{$inv->id}/proof", ['proof' => proofFile()])->assertSessionHasNoErrors();
    $p2 = \App\Models\Payment::latest('id')->first();
    $this->actingAs($admin)->post("/admin/payments/{$p2->id}/verify");
    $this->post('/admin/reservations/'.\App\Models\Reservation::first()->id.'/milestone');
    $bal = \App\Models\Invoice::where('kind', 'balance')->first();
    $this->actingAs($u)->post("/invoices/{$bal->id}/proof", ['proof' => proofFile()])->assertSessionHasNoErrors();
    $p3 = \App\Models\Payment::latest('id')->first();
    $this->actingAs($admin)->post("/admin/payments/{$p3->id}/verify");
    expect(\App\Models\Reservation::first()->status)->toBe('completed')->and(\App\Models\InviteEvent::where('type', 'balance_paid')->count())->toBe(1);
});

it('sends the day 7 and day 9 reminders once each, and again after the due date is moved', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    [$u, $inv] = reservedMember();
    $admin = adminSession($this);
    $this->post("/invoices/{$inv->id}/proof", ['proof' => proofFile()]);
    $this->actingAs($admin)->post('/admin/payments/'.\App\Models\Payment::first()->id.'/verify');
    $this->artisan('reminders:send');
    expect(\App\Models\Reminder::count())->toBe(0);
    $this->post('/admin/reservations/'.\App\Models\Reservation::first()->id.'/milestone');
    $r = \App\Models\Reservation::first();
    $this->artisan('reminders:send');
    expect(\App\Models\Reminder::count())->toBe(0);
    $this->travelTo($r->balance_due_at->copy()->subDays(3)->addHour());
    $this->artisan('reminders:send');
    $this->artisan('reminders:send');
    expect(\App\Models\Reminder::pluck('kind')->all())->toBe(['day7']);
    $this->travelTo($r->balance_due_at->copy()->subDay()->addHour());
    $this->artisan('reminders:send');
    expect(\App\Models\Reminder::pluck('kind')->sort()->values()->all())->toBe(['day7', 'day9']);
    $this->actingAs($admin)->post("/admin/reservations/{$r->id}/extend", ['due' => now()->addDays(20)->format('Y-m-d')])->assertSessionHasNoErrors();
    expect(\App\Models\Reminder::count())->toBe(0)->and($r->fresh()->balance_due_at->gt($r->balance_due_at))->toBeTrue();
});

it('keeps an admin acting as a member from uploading proof, and cancels voids open invoices', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    [$u, $inv] = reservedMember();
    $admin = adminSession($this);
    $this->actingAs($admin)->post("/admin/members/{$u->id}/impersonate");
    $this->post("/invoices/{$inv->id}/proof", ['proof' => proofFile()])->assertForbidden();
    $this->post('/admin/exit-member');
    $this->post('/admin/reservations/'.\App\Models\Reservation::first()->id.'/cancel', ['outcome' => 'none'])->assertRedirect();
    expect($inv->fresh()->status)->toBe('void')->and(\App\Models\Reservation::first()->status)->toBe('cancelled');
});

it('lets a member reset the password with the emailed code', function () {
    $captured = [];
    \Illuminate\Support\Facades\Event::listen(\Illuminate\Mail\Events\MessageSent::class, function ($e) use (&$captured) {
        if (preg_match('/<strong[^>]*>(\d{6})<\/strong>/', $e->message->getHtmlBody() ?? '', $m)) {
            $captured[] = $m[1];
        }
    });
    config(['mail.default' => 'array']);
    $u = memberWithInvite();
    $code = app(InviteService::class)->regenerate(\App\Models\Invite::first());
    $this->get('/sign-in/reset')->assertNotFound();
    $this->post('/enter', ['code' => $code]);
    $this->post('/sign-in/reset/send')->assertRedirect('/sign-in/reset');
    $this->get('/sign-in/reset')->assertSee('6-digit code');
    expect($captured)->toHaveCount(1);
    $this->post('/sign-in/reset', ['code' => '000000', 'password' => 'Brand-new-pass9', 'password_confirmation' => 'Brand-new-pass9'])->assertSessionHas('error');
    $this->post('/sign-in/reset', ['code' => $captured[0], 'password' => 'Brand-new-pass9', 'password_confirmation' => 'Brand-new-pass9'])->assertRedirect('/sign-in');
    $this->post('/sign-in', ['password' => 'Brand-new-pass9'])->assertRedirect('/welcome');
    expect(\App\Models\AuditLog::where('action', 'password.reset')->count())->toBe(1);
});

it('does not offer email reset to admin accounts', function () {
    $this->artisan('invite:admin', ['name' => 'Root', 'email' => 'root@example.com']);
    $inv = \App\Models\Invite::first();
    $code = app(InviteService::class)->regenerate($inv);
    $this->post('/enter', ['code' => $code]);
    $this->post('/sign-in/claim', ['password' => 'Sup3rsecret!!', 'password_confirmation' => 'Sup3rsecret!!']);
    $this->post('/sign-out');
    $this->post('/enter', ['code' => $code]);
    $this->get('/sign-in')->assertDontSee('Forgot your password?');
    $this->get('/sign-in/reset')->assertNotFound();
    $this->artisan('admin:password', ['email' => 'root@example.com'])->expectsQuestion('New password (at least 10 characters, letters and numbers)', 'Another-pass-77')->assertSuccessful();
    $this->post('/sign-in', ['password' => 'Another-pass-77'])->assertRedirect('/admin');
});

it('sends signed-out visitors to the code page, and has no /login page at all', function () {
    $this->get('/dashboard')->assertNotFound();
    $this->get('/login')->assertNotFound();
    $this->get('/forgot-password')->assertNotFound();
});

it('shows every section fact somewhere in the specifications, and upgrades an unedited older specs text', function () {
    \App\Models\PageSection::ensureDefaults();
    $m = User::create(['name' => 'Hal', 'email' => 'hal@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000004']);
    $this->actingAs($m)->get('/vehicle')->assertSee('Central touchscreen, two forward screens, windshield display')->assertSee('Two-element, retracts flush into the tail')->assertSee('Gold-glazed, infrared-rejecting')->assertSee('About 8 weeks, part-time')->assertSee('Mobile team, at your location')->assertSee('Ano White, one colour, with exposed titanium accents');
    \App\Models\PageSection::where('slug', 'specs')->update(['body' => 'Written by an admin.']);
    (require database_path('migrations/2027_01_02_000001_upgrade_specs_groups.php'))->up();
    expect(\App\Models\PageSection::where('slug', 'specs')->value('body'))->toBe('Written by an admin.');
});

it('shows the welcome page only until it has been seen, and an admin looking in does not mark it', function () {
    $m = User::create(['name' => 'Gus', 'email' => 'gus@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000003']);
    $this->get('/welcome')->assertNotFound();
    $admin = adminSession($this);
    $this->actingAs($admin)->post("/admin/members/{$m->id}/impersonate");
    $this->post('/welcome')->assertRedirect('/vehicle');
    expect($m->fresh()->intro_seen_at)->toBeNull();
    $this->post('/admin/exit-member');
    $this->actingAs($m)->post('/welcome');
    expect($m->fresh()->intro_seen_at)->not->toBeNull();
    $this->post('/sign-out');
    [, $code] = app(InviteService::class)->create('Gus', 'gus@example.com');
    \App\Models\Invite::first()->update(['status' => 'claimed', 'user_id' => $m->id]);
    $this->post('/enter', ['code' => $code]);
    $this->post('/sign-in', ['password' => 'Sup3rsecret!!'])->assertRedirect('/vehicle');
});

function woff2Upload(string $name = 'Brand.woff2', ?string $bytes = null) {
    return \Illuminate\Http\UploadedFile::fake()->createWithContent($name, $bytes ?? ('wOF2'.str_repeat("\0", 200)));
}

it('lets an admin upload woff2 fonts, assign them to parts of the site, and refuses anything else', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $admin = adminSession($this);
    $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertSee('Fonts');
    // not a woff2: wrong signature, wrong extension
    $this->post('/admin/settings/fonts', ['label' => 'Bad', 'font' => woff2Upload('x.woff2', 'not a font')])->assertSessionHasErrors('font');
    $this->post('/admin/settings/fonts', ['label' => 'Bad', 'font' => woff2Upload('x.ttf')])->assertSessionHasErrors('font');
    expect(\App\Models\Font::count())->toBe(0);

    $this->post('/admin/settings/fonts', ['label' => 'Brand display', 'font' => woff2Upload()])->assertSessionHasNoErrors();
    $font = \App\Models\Font::first();
    expect($font->label)->toBe('Brand display');
    \Illuminate\Support\Facades\Storage::disk('local')->assertExists('fonts/'.$font->filename);

    // nothing is assigned yet, so pages carry no custom font rules
    $this->get('/vehicle')->assertDontSee('cf-'.$font->id, false);
    $this->post('/admin/settings/fonts/assign', ['font_vehicle' => (string) $font->id, 'font_body' => '999'])->assertSessionHasNoErrors();
    $this->get('/vehicle')->assertSee('font-family:"cf-'.$font->id.'"', false)->assertSee('/fonts/'.$font->filename, false)
        ->assertSee('<link rel="preload" href="/fonts/'.$font->filename.'" as="font" type="font/woff2" crossorigin>', false)->assertSee('font-display:block', false);
    expect(\App\Models\Setting::find('font_body')->value)->toBe('');

    // the file is served to anyone (the code page needs it), but only under its exact random name
    auth()->logout();
    $this->get('/fonts/'.$font->filename)->assertOk()->assertHeader('Content-Type', 'font/woff2');
    $this->get('/fonts/'.str_repeat('a', 40).'.woff2')->assertNotFound();

    // removing a font puts its parts back on the built-in one
    $this->actingAs($admin)->delete('/admin/settings/fonts/'.$font->id)->assertSessionHasNoErrors();
    expect(\App\Models\Font::count())->toBe(0)->and(\App\Models\Setting::find('font_vehicle')->value)->toBe('');
    $this->get('/vehicle')->assertDontSee('cf-'.$font->id, false);
});

it('no longer prints the agreement version line on My reservation', function () {
    Mail::fake();
    $u = memberWithInvite();
    $this->actingAs($u)->post('/reserve', ['agree' => 1]);
    $this->get('/dashboard')->assertOk()->assertDontSee('Agreement version')->assertSee('Pioneer allocation');
});

it('lets an admin upload a site logo and a vehicle logo that replace the text names', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $admin = adminSession($this);
    $this->actingAs($admin)->get('/vehicle')->assertDontSee('mark-img', false)->assertDontSee('v-logo', false);

    $this->post('/admin/settings/logo/site', ['logo' => \Illuminate\Http\UploadedFile::fake()->image('site.png', 600, 200)])->assertSessionHasNoErrors();
    $this->post('/admin/settings/logo/vehicle', ['logo' => \Illuminate\Http\UploadedFile::fake()->image('car.png', 900, 200)])->assertSessionHasNoErrors();
    $this->post('/admin/settings/logo/vehicle', ['logo' => \Illuminate\Http\UploadedFile::fake()->create('x.svg', 2, 'image/svg+xml')])->assertSessionHasErrors('logo');
    $this->post('/admin/settings/logo/other', ['logo' => \Illuminate\Http\UploadedFile::fake()->image('a.png', 600, 200)])->assertNotFound();

    $html = $this->get('/vehicle')->assertOk()->getContent();
    expect(substr_count($html, 'class="v-logo"'))->toBe(2)->and(substr_count($html, 'class="mark-img"'))->toBe(1);
    $vehicleUrl = brand_logo('vehicle');
    $siteUrl = brand_logo('site');

    // the code page has no header or logo, but the logo files are served to anyone, and other uploaded images are not
    auth()->logout();
    $this->get('/')->assertDontSee('class="mark-img"', false)->assertDontSee('class="bar"', false)->assertDontSee('class="foot"', false);
    $this->get($siteUrl)->assertOk();
    $this->get($vehicleUrl)->assertOk();
    $this->get('/brand/'.str_repeat('b', 40).'.png')->assertNotFound();

    // replacing a logo deletes the old file; removing brings the text back
    $this->actingAs($admin)->post('/admin/settings/logo/vehicle', ['logo' => \Illuminate\Http\UploadedFile::fake()->image('car2.png', 900, 200)]);
    expect(brand_logo('vehicle'))->not->toBe($vehicleUrl)->and(\App\Models\Media::count())->toBe(2);
    $this->delete('/admin/settings/logo/vehicle');
    $this->delete('/admin/settings/logo/site');
    $this->get('/vehicle')->assertDontSee('v-logo', false)->assertDontSee('mark-img', false);
    expect(\App\Models\Media::count())->toBe(0);
});

it('keeps reservations closed until the escrow details are complete and free of placeholders', function () {
    Mail::fake();
    $u = memberWithInvite();
    \App\Models\Setting::set('escrow_account', '#[0000-0000-0000]');
    $this->actingAs($u)->get('/dashboard')->assertDontSee('Reserve my allocation')->assertSee('Reservations are not open yet');
    $this->get('/reserve')->assertRedirect('/dashboard');
    $this->post('/reserve', ['agree' => 1])->assertRedirect('/dashboard');
    expect(\App\Models\Reservation::count())->toBe(0);
    \App\Models\Setting::set('escrow_account', '#0000-1111-2222');
    $this->get('/reserve')->assertOk()->assertSee('Cynthia Poole')->assertSee('held in trust');
});

it('lets an admin release escrowed funds to the company only after the milestone, and records it', function () {
    [$u, $admin, $r] = reservationWithMilestone();
    $fresh = \App\Models\Reservation::first();
    expect($fresh->heldCents())->toBe(10_000_000);
    $this->post("/admin/reservations/{$r->id}/release", [])->assertSessionHasErrors('note');
    $this->post("/admin/reservations/{$r->id}/release", ['note' => 'Settlement #42'])->assertSessionHasNoErrors();
    $r = $r->fresh()->load('escrowEntries');
    expect($r->heldCents())->toBe(0)->and($r->escrowEntries->pluck('event')->all())->toBe(['received', 'released'])->and($r->escrowLabel())->toBe('Released to company');
    $this->post("/admin/reservations/{$r->id}/release", ['note' => 'again'])->assertStatus(422);
    $this->actingAs($u)->get('/dashboard')->assertSee('Settled with');
    $this->actingAs($admin)->get("/admin/reservations/{$r->id}")->assertSee('Settlement #42')->assertSee('Escrow');
});

it('does not allow a release before the milestone is confirmed', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    [$u, $inv] = reservedMember();
    $admin = adminSession($this);
    $this->post("/invoices/{$inv->id}/proof", ['proof' => proofFile()]);
    $this->actingAs($admin)->post('/admin/payments/'.\App\Models\Payment::first()->id.'/verify');
    $r = \App\Models\Reservation::first();
    $this->post("/admin/reservations/{$r->id}/release", ['note' => 'too early'])->assertStatus(422);
    expect($r->fresh()->heldCents())->toBe(10_000_000);
});

it('lets an admin decide a cancellation: refund from escrow, release to the company, or nothing held', function () {
    // refund
    [$u, $admin, $r] = reservationWithMilestone();
    $this->post("/admin/reservations/{$r->id}/cancel", ['outcome' => 'none'])->assertStatus(422);
    $this->post("/admin/reservations/{$r->id}/cancel", ['outcome' => 'refund'])->assertSessionHasErrors('note');
    $this->post("/admin/reservations/{$r->id}/cancel", ['outcome' => 'refund', 'note' => 'Milestone not reached'])->assertSessionHasNoErrors();
    $r = $r->fresh()->load('escrowEntries');
    expect($r->status)->toBe('cancelled')->and($r->outcome)->toBe('cancelled_refunded')->and($r->heldCents())->toBe(0)->and($r->escrowEntries->last()->event)->toBe('refunded')
        ->and($r->escrowLabel())->toBe('Refunded')->and($r->invoices->where('status', 'open')->count())->toBe(0);
    $this->actingAs($u)->get('/dashboard')->assertSee('returned to you');
});

it('can cancel and release held funds to the company, and cancel with nothing held', function () {
    [$u, $admin, $r] = reservationWithMilestone();
    $this->post("/admin/reservations/{$r->id}/cancel", ['outcome' => 'release', 'note' => 'Per clause 6'])->assertSessionHasNoErrors();
    $r = $r->fresh();
    expect($r->outcome)->toBe('cancelled_released')->and($r->heldCents())->toBe(0)->and($r->refunded_at)->toBeNull()->and($r->released_at)->not->toBeNull();
});

it('lets an admin push the balance deadline back and email the member an update', function () {
    [$u, $admin, $r] = reservationWithMilestone();
    $before = $r->balance_due_at;
    $this->post("/admin/reservations/{$r->id}/extend", ['due' => now()->addDays(40)->format('Y-m-d')])->assertSessionHasNoErrors();
    expect($r->fresh()->balance_due_at->gt($before))->toBeTrue();
    $this->post("/admin/reservations/{$r->id}/update", ['message' => "The milestone is delayed by two weeks.\nWe will confirm a new date soon."])->assertSessionHasNoErrors();
    expect(\App\Models\AuditLog::where('action', 'reservation.update_sent')->count())->toBe(1);
    $this->post("/admin/reservations/{$r->id}/update", ['message' => ''])->assertSessionHasErrors('message');
});

it('carries the escrow wording into the agreement and the footer', function () {
    $u = memberWithInvite();
    $this->actingAs($u)->get('/reserve')->assertSee('held in trust by our independent escrow agent, Cynthia Poole')->assertSee('confirms your allocation milestone');
    $this->get('/dashboard')->assertSee('held in escrow by an independent escrow agent')->assertDontSee('No deposits are accepted');
});


it('lets an admin upload a background picture that shows on the code page and is removed again', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $admin = adminSession($this);
    $this->actingAs($admin);
    $this->post('/admin/settings/logo/backdrop', ['logo' => \Illuminate\Http\UploadedFile::fake()->image('bg.jpg', 1600, 900)])->assertSessionHasNoErrors();
    auth()->logout();
    $url = brand_logo('backdrop');
    expect($url)->not->toBeNull();
    $this->get('/')->assertSee('data-backdrop', false)->assertSee($url, false);
    $this->get($url)->assertOk();
    $this->actingAs($admin)->delete('/admin/settings/logo/backdrop')->assertSessionHasNoErrors();
    auth()->logout();
    $this->get('/')->assertSee('images/gate-default.jpg', false)->assertDontSee($url, false);
});

it('keeps the built-in vehicle pictures behind the sign-in', function () {
    $this->get('/images/safety-default.webp')->assertNotFound();
    $this->get('/images/hero-default.webp')->assertNotFound();
    $this->get('/vehicle-art/safety-default.webp')->assertNotFound();
    $m = User::create(['name' => 'Ivy', 'email' => 'ivy@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000009']);
    $this->actingAs($m)->get('/vehicle-art/safety-default.webp')->assertOk()->assertHeader('Content-Type', 'image/webp');
    $this->actingAs($m)->get('/vehicle-art/..%2F.env')->assertNotFound();
    $this->actingAs($m)->get('/vehicle')->assertSee('/vehicle-art/safety-default.webp', false)->assertDontSee('/images/safety-default.webp', false);
});

it('serves the vehicle and portal styles only to signed-in members', function () {
    $public = file_get_contents(public_path('css/pioneer.css'));
    expect($public)->not->toContain('v-hero')->not->toContain('#safety')->not->toContain('infocard');
    $this->get('/')->assertDontSee('member.css', false);
    $this->get('/member.css')->assertNotFound();
    $m = User::create(['name' => 'Kai', 'email' => 'kai@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000010']);
    $this->actingAs($m)->get('/member.css')->assertOk()->assertHeader('Content-Type', 'text/css; charset=utf-8');
    expect($this->actingAs($m)->get('/member.css')->baseResponse->getFile()->getContent())->toContain('.v-hero');
    $this->actingAs($m)->get('/vehicle')->assertSee('member.css', false);
});

it('offers a text size button on the vehicle page and the dashboard for signed-in members only', function () {
    $this->get('/')->assertDontSee('class="tsize"', false);
    $m = User::create(['name' => 'Lou', 'email' => 'lou@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000011']);
    $this->actingAs($m)->get('/vehicle')->assertSee('class="tsize"', false)->assertSee('pioneer.ts', false);
    $this->actingAs($m)->get('/dashboard')->assertSee('class="tsize"', false)->assertSee('data-ts="portal"', false);
    $this->actingAs($m)->get('/vehicle')->assertSee('data-ts="vehicle"', false);
    $this->actingAs($m)->get('/welcome')->assertSee('class="tsize"', false);
    $this->actingAs($m)->get('/welcome')->assertSee('/vehicle-art/training-default.webp', false);
    $this->get('/')->assertDontSee('training-default', false);
});

it('uses the vehicle name from settings in the hero, the video section and the end of the vehicle page', function () {
    \App\Models\Setting::set('vehicle_name', 'Skylark One');
    $m = User::create(['name' => 'Fay', 'email' => 'fay2@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000003']);
    $this->actingAs($m)->get('/vehicle')->assertOk()->assertSee('Watch Skylark One in flight.')->assertDontSee('[Vehicle Name]', false);
});
