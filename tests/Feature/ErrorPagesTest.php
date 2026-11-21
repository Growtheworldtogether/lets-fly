<?php

beforeEach(function () {
    $this->artisan('migrate:fresh');
});

it('shows a themed 404 that says nothing about the site', function () {
    $r = $this->get('/no-such-page')->assertNotFound()->assertSee('There is nothing here.')->assertSee('404');
    $html = $r->getContent();
    expect($html)->not->toContain(brand('name'))->not->toContain('vehicle-art')->not->toContain('member.css')->not->toContain('Pioneer');
    // Every unknown address gets the same page.
    expect($this->get('/another-missing-page')->getContent())->toBe($html);
});

it('has a page for every common error', function (int $status, string $text) {
    $html = view("errors.{$status}")->render();
    expect($html)->toContain((string) $status)->toContain($text)->toContain('Back to the start');
})->with([[401, 'Please sign in first.'], [403, 'not open to you'], [404, 'nothing here'], [419, 'timed out'], [429, 'Too many attempts'], [500, 'hit a problem'], [503, 'Briefly offline']]);

it('falls back to a generic page for other 4xx and 5xx codes', function () {
    $e4 = new \Symfony\Component\HttpKernel\Exception\HttpException(418);
    $e5 = new \Symfony\Component\HttpKernel\Exception\HttpException(502);
    expect(view('errors.4xx', ['exception' => $e4])->render())->toContain('418')->toContain('did not work');
    expect(view('errors.5xx', ['exception' => $e5])->render())->toContain('502')->toContain('hit a problem');
});

it('answers a stranger with the same 404 for every address behind the gate', function () {
    $missing = $this->get('/no-such-page')->getContent();
    foreach (['/dashboard', '/vehicle', '/admin', '/admin/members', '/reserve', '/member.css', '/vehicle-art/safety-default.webp'] as $url) {
        $r = $this->get($url)->assertNotFound();
        expect($r->getContent())->toBe($missing);
    }
});

it('sends someone who signed in before back to the code page, and ignores a forged marker', function () {
    $u = \App\Models\User::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'Sup3rsecret!!', 'email_verified_at' => now()]);
    $res = $this->actingAs($u)->get('/dashboard');
    $res->assertCookie('mk', '1');

    auth()->logout();
    $this->flushSession();
    $this->withCookie('mk', '1')->get('/dashboard')->assertRedirect('/');
    $this->withUnencryptedCookie('mk', '1')->get('/dashboard')->assertNotFound();
    $this->withCookie('mk', 'yes')->get('/dashboard')->assertNotFound();
});

it('names the session cookie neutrally, without the site name', function () {
    $res = $this->get('/');
    $names = collect($res->headers->getCookies())->map->getName()->all();
    expect($names)->toContain('sid');
    foreach ($names as $n) {
        expect(strtolower($n))->not->toContain('pioneer')->not->toContain('golden');
    }
});

it('answers a stranger\'s rejected form post with the plain 404, but keeps the 419 for the code page', function () {
    \Illuminate\Support\Facades\Route::middleware('web')->post('/_csrf-probe', fn () => throw new \Illuminate\Session\TokenMismatchException);
    \Illuminate\Support\Facades\Route::middleware('web')->post('/enter-probe', fn () => throw new \Illuminate\Session\TokenMismatchException);
    $missing = $this->get('/no-such-page')->getContent();
    $r = $this->post('/_csrf-probe')->assertNotFound();
    expect($r->getContent())->toBe($missing);
    // Someone who passed the gate or is signed in still sees "page expired".
    $this->withSession(['gate.invite' => 1])->post('/_csrf-probe')->assertStatus(419);
});

it('answers a stranger\'s wrong-method request with the plain 404', function () {
    $missing = $this->get('/no-such-page')->getContent();
    $r = $this->post('/admin/members')->assertNotFound();
    expect($r->getContent())->toBe($missing);
    $this->post('/dashboard')->assertNotFound();
});

it('has no public storage route', function () {
    $this->get('/storage/anything')->assertNotFound();
});

it('words the reset email as a reset, and the sign-in email as a sign-in', function () {
    $reset = view('mail.signin-code', ['purpose' => 'reset', 'code' => '123456', 'name' => 'Ada', 'minutes' => 10])->render();
    $signin = view('mail.signin-code', ['code' => '123456', 'name' => 'Ada', 'minutes' => 10])->render();
    expect($reset)->toContain('password reset code')->toContain('nothing has changed')->not->toContain('sign-in code');
    expect($signin)->toContain('sign-in code')->not->toContain('reset');
});

it('titles the code page neutrally, without the company name', function () {
    \App\Models\Setting::set('company_name', 'Secret Aero Co');
    \App\Models\Setting::set('vehicle_name', 'Secret Falcon');
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('<title>Invitation only</title>')->not->toContain('Secret Aero Co')->not->toContain('Secret Falcon');
});
