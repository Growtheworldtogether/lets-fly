<?php

namespace App\Http\Controllers;

use App\Support\Installer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/** The first-run setup wizard. Reachable only until setup has finished. */
class SetupController extends Controller
{
    /** Step id => label, in order. */
    public const STEPS = ['key' => 'Owner key', 'check' => 'Server check', 'site' => 'Site address', 'database' => 'Database', 'email' => 'Email', 'admin' => 'First administrator', 'review' => 'Install'];

    public function show(Request $request, ?string $step = null)
    {
        $step = $step ?? $this->firstOpen($request);
        abort_unless(isset(self::STEPS[$step]), 404);
        if (! $this->reachable($request, $step)) {
            return redirect()->route('setup', $this->firstOpen($request));
        }

        $data = ['step' => $step, 'steps' => self::STEPS, 'd' => $request->session()->get('setup', []), 'done' => $this->done($request)];
        if ($step === 'key') {
            $data['keyFile'] = 'storage/app/install-key.txt';
            $data['local'] = ! Installer::keyRequired($request);
            Installer::key();
        }
        if ($step === 'check') {
            $data['rows'] = Installer::requirements();
            $data['blocked'] = collect($data['rows'])->contains(fn ($r) => $r['blocking'] && ! $r['ok']);
        }
        if ($step === 'site') {
            $data['tzs'] = Installer::timezones();
            $data['suggestedUrl'] = rtrim($request->getSchemeAndHttpHost(), '/');
        }

        return view('setup.step', $data);
    }

    public function save(Request $request, string $step)
    {
        abort_unless(isset(self::STEPS[$step]), 404);
        abort_unless($this->reachable($request, $step), 403);

        $result = $this->{'save'.ucfirst($step)}($request);
        if ($result !== null) {
            return $result;
        }

        $done = $this->done($request);
        $done[$step] = true;
        $request->session()->put('setup_done', $done);
        $keys = array_keys(self::STEPS);
        $next = $keys[array_search($step, $keys, true) + 1] ?? 'review';

        return redirect()->route('setup', $next);
    }

    private function saveKey(Request $request)
    {
        if (! Installer::keyRequired($request)) {
            return null;
        }
        // Counted in the file cache: there is no database yet while setup runs. (Tests use the default store.)
        $cache = Cache::store(app()->runningUnitTests() ? null : 'file');
        $limit = 'setup-key:'.sha1((string) $request->ip());
        $tries = (int) $cache->get($limit, 0);
        if ($tries >= 5) {
            throw ValidationException::withMessages(['key' => 'Too many tries. Wait 15 minutes, then try again.']);
        }
        $request->validate(['key' => ['required', 'string']]);
        if (! hash_equals(Installer::key(), strtoupper(trim((string) $request->input('key'))))) {
            $cache->put($limit, $tries + 1, 900);
            throw ValidationException::withMessages(['key' => 'That is not the key in the file. Open the file on the server and copy it exactly.']);
        }
        $cache->forget($limit);

        return null;
    }

    private function saveCheck(Request $request)
    {
        if (collect(Installer::requirements())->contains(fn ($r) => $r['blocking'] && ! $r['ok'])) {
            throw ValidationException::withMessages(['check' => 'Fix the items marked as missing first, then check again.']);
        }

        return null;
    }

    private function saveSite(Request $request)
    {
        $v = $request->validate([
            'url' => ['required', 'url:http,https', 'max:200'],
            'tz' => ['required', fn ($a, $val, $fail) => Installer::validTimezone((string) $val) ?: $fail('Choose a time zone from the list.')],
        ]);
        $request->session()->put('setup.url', rtrim($v['url'], '/'));
        $request->session()->put('setup.tz', $v['tz']);

        return null;
    }

    private function saveDatabase(Request $request)
    {
        $driver = $request->validate(['driver' => ['required', 'in:sqlite,mysql']])['driver'];
        $db = ['driver' => $driver];
        if ($driver === 'mysql') {
            $db = $request->validate([
                'driver' => ['required'], 'host' => ['required', 'string', 'max:160'], 'port' => ['required', 'integer', 'between:1,65535'],
                'name' => ['required', 'string', 'max:64'], 'user' => ['required', 'string', 'max:64'], 'pass' => ['nullable', 'string', 'max:200'],
            ]);
        }
        if ($error = Installer::testDatabase($db)) {
            throw ValidationException::withMessages(['driver' => $error]);
        }
        $request->session()->put('setup.db', $db);

        return null;
    }

    private function saveEmail(Request $request)
    {
        $mode = $request->validate(['mode' => ['required', 'in:smtp,later']])['mode'];
        $mail = ['mode' => $mode];
        if ($mode === 'smtp') {
            $mail += $request->validate([
                'host' => ['required', 'string', 'max:160'], 'port' => ['required', 'integer', 'between:1,65535'],
                'user' => ['required', 'string', 'max:200'], 'pass' => ['required', 'string', 'max:200'], 'from' => ['required', 'email', 'max:160'],
            ]);
            if ($error = Installer::testMail($mail['host'], (int) $mail['port'])) {
                throw ValidationException::withMessages(['host' => $error]);
            }
        }
        $request->session()->put('setup.mail', $mail);

        return null;
    }

    private function saveAdmin(Request $request)
    {
        $v = $request->validate(['name' => ['required', 'string', 'min:3', 'max:120'], 'email' => ['required', 'email', 'max:160']]);
        $request->session()->put('setup.admin', ['name' => $v['name'], 'email' => $v['email']]);

        return null;
    }

    private function saveReview(Request $request)
    {
        $d = $request->session()->get('setup', []);
        foreach (['url', 'tz', 'db', 'mail', 'admin'] as $k) {
            if (empty($d[$k])) {
                return redirect()->route('setup', 'site')->withErrors(['setup' => 'Some answers are missing. Go through the steps again.']);
            }
        }
        $d['live'] = $request->boolean('live');

        try {
            $code = Installer::install($d);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['install' => 'Setup did not finish: '.preg_replace('/\s+/', ' ', mb_substr($e->getMessage(), 0, 300)).' Nothing is locked yet, so fix it and try again.']);
        }
        $request->session()->forget(['setup', 'setup_done']);

        // Shown here, once. /setup is gone from now on, so this page is not reachable again.
        return response()->view('setup.done', ['code' => $code, 'email' => $d['admin']['email'], 'url' => $d['url']])->header('Cache-Control', 'no-store');
    }

    private function done(Request $request): array
    {
        return (array) $request->session()->get('setup_done', []);
    }

    private function firstOpen(Request $request): string
    {
        foreach (array_keys(self::STEPS) as $s) {
            if ($s !== 'review' && empty($this->done($request)[$s])) {
                return $s;
            }
        }

        return 'review';
    }

    /** A step opens once every step before it is done. */
    private function reachable(Request $request, string $step): bool
    {
        foreach (array_keys(self::STEPS) as $s) {
            if ($s === $step) {
                return true;
            }
            if (empty($this->done($request)[$s])) {
                return false;
            }
        }

        return false;
    }
}
