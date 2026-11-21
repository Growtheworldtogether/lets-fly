<?php

use App\Models\Invite;
use App\Models\User;
use App\Services\BulkInvites;

beforeEach(function () {
    $this->artisan('migrate:fresh');
    $this->admin = User::create(['name' => 'Root', 'email' => 'root@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'admin', 'email_verified_at' => now()]);
    \App\Services\StaffDirectory::assign($this->admin, 'Super Admin');
});

it('reads one person per line, with an optional email after a comma or a tab', function () {
    $r = BulkInvites::parse("Jane Doe\n\n  John   Smith , john@example.com \nSmith, Anna\nLee Kim\tlee@example.com\n x \nBad Email, nope@\nJane Doe");
    $by = collect($r['rows'])->keyBy(fn ($x, $i) => $i);
    expect($r['rows'])->toHaveCount(7);
    expect($by[0])->toMatchArray(['name' => 'Jane Doe', 'email' => null, 'status' => 'ok']);
    expect($by[1])->toMatchArray(['name' => 'John Smith', 'email' => 'john@example.com', 'status' => 'ok']);
    expect($by[2])->toMatchArray(['name' => 'Smith, Anna', 'email' => null, 'status' => 'ok']);   // a comma in a name is not an email
    expect($by[3])->toMatchArray(['name' => 'Lee Kim', 'email' => 'lee@example.com', 'status' => 'ok']);
    expect($by[4]['status'])->toBe('bad');                                                       // one letter
    expect($by[5]['status'])->toBe('bad');                                                       // email does not look right
    expect($by[6])->toMatchArray(['name' => 'Jane Doe', 'status' => 'known'])->and($by[6]['create'])->toBeFalse();
    expect(BulkInvites::parse("Jane Doe\nJane Doe", true)['rows'][1]['create'])->toBeTrue();
});

it('flags names that already have an invite and emails that are taken, and caps the list', function () {
    app(\App\Services\InviteService::class)->create('Existing Person', null);
    app(\App\Services\InviteService::class)->create('Revoked Person', null)[0]->update(['status' => 'revoked']);
    $r = BulkInvites::parse("existing person\nRevoked Person\nNew One, root@example.com");
    expect($r['rows'][0]['status'])->toBe('known')->and($r['rows'][1]['status'])->toBe('ok')->and($r['rows'][2]['status'])->toBe('bad');
    $many = BulkInvites::parse(implode("\n", array_map(fn ($i) => "Person $i x", range(1, 250))));
    expect($many['tooMany'])->toBeTrue()->and($many['rows'])->toHaveCount(200);
});

it('reviews, creates, shows each code once with a CSV, and can hide them', function () {
    $this->actingAs($this->admin);
    $names = "Jane Doe\nJohn Smith, john@example.com\n=cmd|' /C calc'!A0 Evil";
    $this->post('/admin/invites/bulk/review', ['names' => $names, 'label' => 'November launch'])
        ->assertOk()->assertSee('Create 3 invites')->assertSee('Nothing has been created yet');
    expect(Invite::count())->toBe(0);

    $this->post('/admin/invites/bulk', ['names' => $names, 'label' => 'November launch'])->assertRedirect('/admin/invites/bulk/done');
    expect(Invite::count())->toBe(3)->and(Invite::where('label', 'November launch')->count())->toBe(3);
    expect(Invite::where('name', 'John Smith')->first()->email)->toBe('john@example.com');
    expect(\App\Models\AuditLog::where('action', 'invite.bulk_created')->count())->toBe(1);

    $page = $this->get('/admin/invites/bulk/done')->assertOk()->assertSee('3 invites created')->assertSee('Copy all');
    preg_match_all('/class="mono">([A-Z0-9-]{14,})</', $page->getContent(), $m);
    expect($m[1])->toHaveCount(3);
    foreach ($m[1] as $code) {
        expect(app(\App\Services\InviteService::class)->find($code))->not->toBeNull();   // the shown code really works
    }
    $this->get('/admin/invites/bulk/done')->assertOk();                                  // a refresh keeps them

    $csv = $this->get('/admin/invites/bulk/done.csv')->assertOk();
    expect($csv->headers->get('content-type'))->toContain('text/csv');
    $text = $csv->streamedContent();
    expect($text)->toContain('Name,Email,"Invite code",Note')->toContain($m[1][0])->toContain("'=cmd");   // a leading = is neutralised

    $this->post('/admin/invites/bulk/clear')->assertRedirect('/admin/invites');
    $this->get('/admin/invites/bulk/done')->assertRedirect('/admin/invites');
    $this->get('/admin/invites/bulk/done.csv')->assertNotFound();
    expect(\App\Models\InviteEvent::where('type', 'created')->count())->toBe(3);
});

it('skips names that already have an invite unless you choose to include them', function () {
    $this->actingAs($this->admin);
    app(\App\Services\InviteService::class)->create('Jane Doe', null);
    $this->post('/admin/invites/bulk', ['names' => "Jane Doe\nNew Person"]);
    expect(Invite::count())->toBe(2);                                                    // only New Person was added
    $this->post('/admin/invites/bulk', ['names' => "Jane Doe\nNew Person 2", 'include_known' => 1]);
    expect(Invite::where('name', 'Jane Doe')->count())->toBe(2);
});

it('forgets the codes after 15 minutes', function () {
    $this->actingAs($this->admin);
    $this->post('/admin/invites/bulk', ['names' => 'Jane Doe']);
    $this->get('/admin/invites/bulk/done')->assertOk();
    $this->travel(16)->minutes();
    $this->get('/admin/invites/bulk/done')->assertRedirect('/admin/invites');
});

it('keeps bulk invites away from members and strangers', function () {
    $m = User::create(['name' => 'Fay', 'email' => 'fay@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000002']);
    $this->post('/admin/invites/bulk', ['names' => 'Jane Doe'])->assertNotFound();
    $this->actingAs($m)->post('/admin/invites/bulk', ['names' => 'Jane Doe'])->assertForbidden();
    $this->actingAs($m)->post('/admin/invites/bulk/review', ['names' => 'Jane Doe'])->assertForbidden();
    expect(Invite::count())->toBe(0);
});

it('shows the paste box on the invites page', function () {
    $this->actingAs($this->admin)->get('/admin/invites')->assertOk()->assertSee('Add many people at once')->assertSee('One name per line');
});
