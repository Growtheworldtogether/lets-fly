<?php

namespace App\Support;

use App\Models\User;
use App\Services\InviteService;
use App\Services\StaffDirectory;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/** First-run setup: the checks, the settings file, the tables and the first administrator's invite code. */
final class Installer
{
    private static ?bool $memo = null;

    public static function installed(): bool
    {
        if (app()->runningUnitTests() && ! config('setup.check_in_tests')) {
            return true;
        }
        if (self::$memo !== null) {
            return self::$memo;
        }
        if (is_file(config('setup.lock'))) {
            return self::$memo = true;
        }
        // A site that was running before setup existed already has accounts: mark it installed instead of locking people out.
        try {
            if (Schema::hasTable('users') && User::query()->exists()) {
                @file_put_contents(config('setup.lock'), 'Installed '.date('c').PHP_EOL);

                return self::$memo = true;
            }
        } catch (\Throwable) {
        }

        return false;
    }

    public static function forget(): void
    {
        self::$memo = null;
    }

    /** Only a browser on this same machine skips the owner key. Anything arriving through a proxy or from another address must show it. */
    public static function keyRequired(Request $request): bool
    {
        $local = in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true) && ! $request->hasHeader('X-Forwarded-For') && ! $request->hasHeader('Forwarded');

        return ! $local;
    }

    /** The owner key. Made on first use and kept in a file only someone with server access can read. */
    public static function key(): string
    {
        $file = config('setup.key_file');
        if (! is_file($file) || strlen(trim((string) @file_get_contents($file))) < 8) {
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $key = '';
            for ($i = 0; $i < 8; $i++) {
                $key .= $chars[random_int(0, strlen($chars) - 1)];
            }
            @file_put_contents($file, $key.PHP_EOL);
        }

        return trim((string) @file_get_contents($file));
    }

    /** @return list<array{label:string, ok:bool, note:string, blocking:bool}> */
    public static function requirements(): array
    {
        $rows = [['PHP 8.3 or newer', PHP_VERSION_ID >= 80300, 'This server runs '.PHP_VERSION.'.', true]];
        foreach (['ctype', 'curl', 'fileinfo', 'gd', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'zip'] as $ext) {
            $rows[] = ['PHP extension: '.$ext, extension_loaded($ext), extension_loaded($ext) ? 'Installed.' : 'Ask your host to turn it on.', true];
        }
        foreach (['storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'] as $dir) {
            $ok = is_dir(base_path($dir)) && is_writable(base_path($dir));
            $rows[] = ['Writable folder: '.$dir, $ok, $ok ? 'Writable.' : 'Make this folder writable by the web server.', true];
        }
        $envOk = is_file(base_path('.env')) ? is_writable(base_path('.env')) : is_writable(base_path());
        $rows[] = ['Settings file (.env) can be written', $envOk, $envOk ? 'Ready.' : 'The app folder or .env must be writable during setup.', true];
        $https = request()->isSecure() || strtolower((string) request()->header('X-Forwarded-Proto')) === 'https';
        $rows[] = ['Secure connection (https)', $https, $https ? 'Yes.' : 'Not on https. Fine on your own computer; a live site needs it.', false];

        return array_map(fn ($r) => ['label' => $r[0], 'ok' => $r[1], 'note' => $r[2], 'blocking' => $r[3]], $rows);
    }

    public static function timezones(): array
    {
        return ['America/New_York' => 'Eastern', 'America/Chicago' => 'Central', 'America/Denver' => 'Mountain', 'America/Phoenix' => 'Arizona', 'America/Los_Angeles' => 'Pacific', 'America/Anchorage' => 'Alaska', 'Pacific/Honolulu' => 'Hawaii', 'UTC' => 'UTC'];
    }

    public static function validTimezone(string $tz): bool
    {
        return in_array($tz, DateTimeZone::listIdentifiers(), true);
    }

    /** Tries the connection. Returns an error sentence, or null when it works. */
    public static function testDatabase(array $db): ?string
    {
        try {
            if (($db['driver'] ?? 'sqlite') === 'sqlite') {
                $path = database_path('database.sqlite');
                if (! is_file($path) && @file_put_contents($path, '') === false) {
                    return 'Could not create database/database.sqlite. Make the database folder writable.';
                }

                return null;
            }
            $pdo = new \PDO(sprintf('mysql:host=%s;port=%d;dbname=%s', $db['host'], (int) $db['port'], $db['name']), $db['user'], $db['pass'] ?? '', [\PDO::ATTR_TIMEOUT => 5, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            $pdo->query('select 1');
        } catch (\Throwable $e) {
            return 'Could not connect: '.preg_replace('/\s+/', ' ', mb_substr($e->getMessage(), 0, 160));
        }

        return null;
    }

    /** Opens the mail server's port. Returns an error sentence, or null when something answers. */
    public static function testMail(string $host, int $port): ?string
    {
        $errno = 0;
        $err = '';
        $s = @fsockopen($host, $port, $errno, $err, 5);
        if (! $s) {
            return "Could not reach {$host} on port {$port}".($err ? " ({$err})" : '').'. Check the host and port, or choose to set email up later.';
        }
        fclose($s);

        return null;
    }

    /** Checks that a GitHub token can read the repository the updates come from. Returns an error sentence, or null. */
    public static function testUpdates(string $repo, string $token): ?string
    {
        try {
            $r = Http::withHeaders(['User-Agent' => 'LetsFly-setup', 'Accept' => 'application/vnd.github+json'])->withToken($token)->timeout(10)->get("https://api.github.com/repos/{$repo}");
        } catch (\Throwable) {
            return 'Could not reach GitHub from this server. Check the connection, or leave the token empty and add UPDATE_TOKEN to .env later.';
        }
        if ($r->status() === 401) {
            return 'GitHub did not accept that token. Check that you copied all of it and that it has not expired.';
        }
        if ($r->status() === 404) {
            return "That token cannot see {$repo}. When you make the token, choose that repository and give it Contents: Read-only.";
        }

        return $r->successful() ? null : 'GitHub answered with an error ('.$r->status().'). Try again in a moment.';
    }

    /** Sets or adds KEY=value lines in the settings file, creating it from .env.example when there is none. */
    public static function writeEnv(array $values, ?string $path = null): void
    {
        $path ??= base_path('.env');
        $env = is_file($path) ? (string) file_get_contents($path) : (string) @file_get_contents(base_path('.env.example'));
        foreach ($values as $key => $value) {
            $value = (string) $value;
            $line = $key.'='.(preg_match('/[\s#"\'\\\\$]/', $value) ? '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"' : $value);
            $pattern = '/^#?\s*'.preg_quote($key, '/').'=.*$/m';
            $env = preg_match($pattern, $env)
                ? preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $env, 1)
                : rtrim($env).PHP_EOL.$line.PHP_EOL;
        }
        if (@file_put_contents($path, $env) === false) {
            throw new \RuntimeException('Could not write the .env settings file. Make the app folder writable and try again.');
        }
        @chmod($path, 0640);
    }

    /**
     * Applies the answers: settings file, tables, first administrator's invite code, lock.
     *
     * @return string the administrator's invite code, shown once
     */
    public static function install(array $d): string
    {
        $db = $d['db'];
        $mail = $d['mail'];
        $live = (bool) ($d['live'] ?? false);

        $env = [
            'APP_ENV' => $live ? 'production' : 'local',
            'APP_DEBUG' => $live ? 'false' : 'true',
            'APP_URL' => rtrim($d['url'], '/'),
            'APP_TIMEZONE' => $d['tz'],
            'SESSION_SECURE_COOKIE' => str_starts_with($d['url'], 'https://') ? 'true' : 'false',
            'DB_CONNECTION' => $db['driver'],
        ];
        if (! config('app.key')) {
            $env['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
        }
        if ($db['driver'] === 'sqlite') {
            $env['DB_DATABASE'] = database_path('database.sqlite');
        }
        if ($db['driver'] === 'mysql') {
            $env += ['DB_HOST' => $db['host'], 'DB_PORT' => $db['port'], 'DB_DATABASE' => $db['name'], 'DB_USERNAME' => $db['user'], 'DB_PASSWORD' => $db['pass'] ?? ''];
        }
        $env['MAIL_FROM_NAME'] = $mail['sender'];
        if (! empty($d['updates']['token'])) {
            $env['UPDATE_TOKEN'] = $d['updates']['token'];
        }
        if (! empty($d['updates']['repo'])) {
            $env['UPDATE_REPO'] = $d['updates']['repo'];
        }
        if ($mail['mode'] === 'smtp') {
            $env += ['MAIL_MAILER' => 'smtp', 'MAIL_HOST' => $mail['host'], 'MAIL_PORT' => $mail['port'], 'MAIL_USERNAME' => $mail['user'], 'MAIL_PASSWORD' => $mail['pass'], 'MAIL_FROM_ADDRESS' => $mail['from']];
        } else {
            $env += ['MAIL_MAILER' => 'log'];
        }
        self::writeEnv($env);

        // Point this request at the database that was just chosen.
        config(['database.default' => $db['driver']]);
        if ($db['driver'] === 'mysql') {
            config(['database.connections.mysql.host' => $db['host'], 'database.connections.mysql.port' => $db['port'], 'database.connections.mysql.database' => $db['name'], 'database.connections.mysql.username' => $db['user'], 'database.connections.mysql.password' => $db['pass'] ?? '']);
        } else {
            config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
        }
        DB::purge();
        // Until the install is done, cache and sessions use files; the permission registrar keeps its own cache store.
        config(['cache.default' => 'file', 'session.driver' => 'file', 'permission.cache.store' => 'file']);
        app('cache')->forgetDriver('database');
        app('cache')->forgetDriver('file');
        app()->forgetInstance(\Spatie\Permission\PermissionRegistrar::class);
        config(['app.timezone' => $d['tz']]);
        date_default_timezone_set($d['tz']);

        // Not installed, so any tables here are left over from a try that stopped part way. Start them over.
        if (Schema::hasTable('migrations') && ! (Schema::hasTable('users') && User::query()->exists())) {
            Artisan::call('db:wipe', ['--force' => true]);
        }
        Artisan::call('migrate', ['--force' => true]);
        StaffDirectory::sync();
        \App\Models\Setting::set('sender_name', $mail['sender']);

        $a = $d['admin'];
        [, $code] = app(InviteService::class)->create($a['name'], strtolower($a['email']), 'admin');

        try {
            if (! file_exists(public_path('storage'))) {
                Artisan::call('storage:link');
            }
        } catch (\Throwable) {
            // Some hosts forbid symbolic links; the admin pages say so if uploads cannot be shown.
        }

        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $c) {
            try {
                Artisan::call($c);
            } catch (\Throwable) {
            }
        }
        Cache::flush();

        @file_put_contents(config('setup.lock'), 'Installed '.date('c').PHP_EOL);
        @unlink(config('setup.key_file'));
        self::$memo = true;

        return $code;
    }
}
