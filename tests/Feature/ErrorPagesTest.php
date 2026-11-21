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
