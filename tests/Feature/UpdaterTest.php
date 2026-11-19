<?php

use App\Models\AuditLog;
use App\Services\Updater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(fn () => \App\Services\StaffDirectory::sync());

/** A throwaway site: [base, public]. public_html puts the public files in a folder beside the app. */
function fakeSite(bool $publicHtml = false): array
{
    $root = sys_get_temp_dir().'/gw-site-'.uniqid();
    $base = $publicHtml ? $root.'/app' : $root;
    $public = $publicHtml ? $root.'/public_html' : $root.'/public';
    foreach ([$base.'/app', $base.'/storage/app', $public, $base.'/bootstrap/cache'] as $d) {
        File::ensureDirectoryExists($d);
    }
    // The base needs its own public folder for layout detection in the standard layout.
    if ($publicHtml) {
        File::ensureDirectoryExists($base.'/public');
    }
    file_put_contents($base.'/.env', 'APP_KEY=keep-me');
    file_put_contents($base.'/app/Same.php', 'same');
    file_put_contents($base.'/app/Old.php', 'old');
    file_put_contents($base.'/storage/app/upload.txt', 'member upload');
    file_put_contents($base.'/version.json', json_encode(['tag' => 'release-20260101-000000']));

    return [$base, $public, $root];
}

/** @param array<string,string> $files */
function fakeZip(array $files, string $tag = 'release-20260201-000000'): string
{
    $path = sys_get_temp_dir().'/gw-zip-'.uniqid().'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    foreach ($files as $name => $body) {
        $zip->addFromString($name, $body);
    }
    $zip->close();

    return $path;
}

it('installs a package, keeps settings, uploads and the database, and can undo it', function () {
    [$base, $public] = fakeSite();
    file_put_contents($base.'/database.sqlite', 'data');
    $zip = fakeZip([
        'artisan' => '#!php', 'vendor/autoload.php' => '<?php', 'version.json' => json_encode(['tag' => 'release-20260201-000000']),
        'app/Same.php' => 'same', 'app/Old.php' => 'new', 'app/Added.php' => 'added',
        '.env' => 'APP_KEY=attacker', 'storage/app/upload.txt' => 'overwritten', 'database.sqlite' => 'wiped', 'public/index.php' => 'idx',
    ]);
    $u = new Updater($base, $public);

    $r = $u->install($zip);

    expect($r['to'])->toBe('release-20260201-000000')->and($r['added'])->toBeGreaterThan(0)
        ->and(file_get_contents($base.'/app/Old.php'))->toBe('new')
        ->and(file_get_contents($base.'/app/Added.php'))->toBe('added')
        ->and(file_get_contents($base.'/.env'))->toBe('APP_KEY=keep-me')
        ->and(file_get_contents($base.'/storage/app/upload.txt'))->toBe('member upload')
        ->and(file_get_contents($base.'/database.sqlite'))->toBe('data')
        ->and(file_get_contents($public.'/index.php'))->toBe('idx')
        ->and($u->current()['tag'])->toBe('release-20260201-000000');

    $u->restore($r['backup']);
    expect(file_get_contents($base.'/app/Old.php'))->toBe('old')->and(is_file($base.'/app/Added.php'))->toBeFalse()
        ->and($u->current()['tag'])->toBe('release-20260101-000000');
});

it('refuses a package that is not a release, is unsafe, or has the wrong layout', function () {
    [$base, $public] = fakeSite();
    $u = new Updater($base, $public);

    expect(fn () => $u->inspect(fakeZip(['artisan' => 'x', 'vendor/autoload.php' => 'x'])))->toThrow(RuntimeException::class, 'no version information');
    expect(fn () => $u->inspect(fakeZip(['version.json' => '{"tag":"t"}', 'vendor/autoload.php' => 'x'])))->toThrow(RuntimeException::class, 'folder layout');
    expect(fn () => $u->inspect(fakeZip(['version.json' => '{"tag":"t"}', 'artisan' => 'x', 'vendor/autoload.php' => 'x', 'app/../../evil.php' => 'x'])))->toThrow(RuntimeException::class, 'unsafe');
    expect(fn () => $u->inspect(fakeZip(['version.json' => '{"tag":"t"}', 'artisan' => 'x', 'vendor/autoload.php' => 'x']), 'other-tag'))->toThrow(RuntimeException::class, 'not other-tag');
});

it('puts the right files in public_html and the app folder, and keeps the host\'s .htaccess', function () {
    [$base, $public] = fakeSite(true);
    file_put_contents($public.'/.htaccess', 'cpanel php handler');
    $zip = fakeZip([
        'app/artisan' => 'x', 'app/vendor/autoload.php' => '<?php', 'app/version.json' => json_encode(['tag' => 'release-20260201-000000']),
        'app/app/Old.php' => 'new', 'app/public/index.php' => 'must-not-land', 'public_html/index.php' => 'front', 'public_html/.htaccess' => 'laravel rules', 'public_html/css/a.css' => 'a{}',
    ]);
    $u = new Updater($base, $public);

    expect($u->layout())->toBe('public_html')->and($u->assetName())->toBe('lets-fly-release-public_html.zip');
    $u->install($zip);

    expect(file_get_contents($base.'/app/Old.php'))->toBe('new')
        ->and(file_get_contents($public.'/index.php'))->toBe('front')
        ->and(file_get_contents($public.'/css/a.css'))->toBe('a{}')
        ->and(file_get_contents($public.'/.htaccess'))->toBe('cpanel php handler')
        ->and(is_file($base.'/public/index.php'))->toBeFalse();
});

it('rolls back and reports when a file cannot be written', function () {
    [$base, $public] = fakeSite();
    mkdir($base.'/app/Old.php.gw-new'); // a directory where the temporary file must go
    $zip = fakeZip(['artisan' => 'x', 'vendor/autoload.php' => '<?php', 'version.json' => '{"tag":"release-20260201-000000"}', 'app/Added.php' => 'added', 'app/Old.php' => 'new']);
    $u = new Updater($base, $public);

    expect(fn () => $u->install($zip))->toThrow(Exception::class);
    expect(file_get_contents($base.'/app/Old.php'))->toBe('old')->and(is_file($base.'/app/Added.php'))->toBeFalse()->and($u->job())->toBeNull();
});

it('reads the newest release from GitHub, picks the asset for this layout and checks the fingerprint', function () {
    [$base, $public] = fakeSite();
    $zip = fakeZip(['artisan' => 'x', 'vendor/autoload.php' => '<?php', 'version.json' => '{"tag":"release-20260201-000000"}']);
    $body = file_get_contents($zip);
    Http::fake([
        'api.github.com/repos/*/releases/latest' => Http::response(['tag_name' => 'release-20260201-000000', 'name' => 'v', 'body' => 'notes', 'assets' => [
            ['id' => 7, 'name' => 'lets-fly-release.zip', 'size' => strlen($body), 'digest' => 'sha256:'.hash('sha256', $body)],
            ['id' => 8, 'name' => 'lets-fly-release-public_html.zip', 'size' => 1],
        ]]),
        'api.github.com/repos/*/releases/assets/7' => Http::response($body),
    ]);
    $u = new Updater($base, $public);

    $latest = $u->latest();
    expect($latest['asset_id'])->toBe(7)->and($u->isNewer($latest))->toBeTrue();
    expect(basename($u->download($latest)))->toBe('release-20260201-000000.zip');

    $latest['digest'] = str_repeat('0', 64);
    expect(fn () => $u->download($latest))->toThrow(RuntimeException::class, 'fingerprint');
});

it('says clearly when GitHub has no release yet', function () {
    [$base, $public] = fakeSite();
    Http::fake(['api.github.com/*' => Http::response([], 404)]);
    expect(fn () => (new Updater($base, $public))->latest())->toThrow(RuntimeException::class, 'No releases were found');
});

it('lets only a Super Admin open the System page', function () {
    $this->actingAs(adminSession($this, 'Manager'))->get('/admin/system')->assertForbidden();
    $super = adminSession($this);
    $this->actingAs($super)->get('/admin/system')->assertOk()->assertSee('Check for updates');
    $this->actingAs(adminSession($this, 'Manager'))->post('/admin/system/check')->assertForbidden();
});

it('checks for updates from the page, and starts and cancels a job, without touching real files', function () {
    [$base, $public] = fakeSite();
    app()->bind(Updater::class, fn () => new Updater($base, $public));
    Cache::forget('updates.latest');
    Http::fake(['api.github.com/repos/*/releases/latest' => Http::response(['tag_name' => 'release-20260301-120000', 'assets' => [['id' => 1, 'name' => 'lets-fly-release.zip', 'size' => 1]]])]);
    $super = adminSession($this);

    $this->actingAs($super)->post('/admin/system/check')->assertSessionHas('status');
    $this->actingAs($super)->get('/admin/system')->assertSee('A new version is ready');
    $this->actingAs($super)->post('/admin/system/update')->assertRedirect('/admin/system/progress');
    expect((new Updater($base, $public))->job()['phase'])->toBe('download');

    $this->actingAs($super)->post('/admin/system/cancel')->assertRedirect('/admin/system');
    expect((new Updater($base, $public))->job())->toBeNull()
        ->and(AuditLog::where('action', 'system.update_cancelled')->exists())->toBeTrue();
});

it('keeps the update pages reachable while the site is down for an update', function () {
    $admin = adminSession($this);
    Artisan::call('down', ['--secret' => 'abc']);
    try {
        $this->get('/sign-in')->assertStatus(503);
        $this->actingAs($admin)->get('/admin/system/progress')->assertRedirect(route('admin.system'));
    } finally {
        Artisan::call('up');
    }
});
