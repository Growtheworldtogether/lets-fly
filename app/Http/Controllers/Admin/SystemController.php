<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Audit;
use App\Services\Updater;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Admin, System. Shows which version is installed and installs updates from the Releases page of the GitHub
 * repository. Super Admin only, and the password is asked for before anything changes.
 */
class SystemController extends Controller
{
    private const LATEST_KEY = 'updates.latest';

    private const LAST_KEY = 'update_last';

    public function show(Updater $updater): View
    {
        Gate::authorize('admin.manage');
        $latest = Cache::get(self::LATEST_KEY);
        $current = $updater->current();

        return view('admin.system', [
            'current' => $current,
            'latest' => $latest,
            'newer' => is_array($latest) && $updater->isNewer($latest, $current),
            'backup' => $updater->lastBackup(),
            'last' => json_decode(Setting::value(self::LAST_KEY, ''), true) ?: null,
            'repo' => (string) config('updates.repo'),
            'layout' => $updater->layout(),
            'job' => $updater->job(),
            'private' => (string) config('updates.token') !== '',
        ]);
    }

    /** "Check for updates": asks GitHub for the newest release and remembers it for the page. */
    public function check(Updater $updater): RedirectResponse
    {
        Gate::authorize('admin.manage');
        try {
            $latest = $updater->latest();
        } catch (Throwable $e) {
            return back()->with('error', 'Could not check for updates: '.$e->getMessage());
        }
        Cache::put(self::LATEST_KEY, $latest, now()->addDay());

        return back()->with('status', $updater->isNewer($latest)
            ? 'A new version is ready to install: '.self::versionName($latest['tag']).'.'
            : 'You have the latest version.');
    }

    /** "Install update": starts the update job; the progress page runs it in short steps. */
    public function install(Updater $updater): RedirectResponse
    {
        Gate::authorize('admin.manage');
        try {
            $latest = Cache::get(self::LATEST_KEY);
            $updater->start(is_array($latest) ? $latest : $updater->latest());
        } catch (Throwable $e) {
            return back()->with('error', 'The update did not start: '.$e->getMessage());
        }

        return redirect()->route('admin.system.progress');
    }

    /**
     * Runs the next short step of the update and shows how far it is, then reloads itself. Download and compare
     * leave the site up; only the write step shows members "Back in a minute" (the administrator passes through).
     * The last step runs on the new code: database changes, fresh caches, site back up.
     */
    public function progress(Request $request, Updater $updater): Response|RedirectResponse
    {
        Gate::authorize('admin.manage');
        $job = $updater->job();
        if (! $job) {
            return redirect()->route('admin.system');
        }
        if ($job['phase'] === 'finish') {
            return $this->finishJob($request, $updater, $job);
        }

        $cookie = null;
        try {
            if ($job['phase'] === 'write' && ! app()->isDownForMaintenance()) {
                $secret = Str::random(40);
                Artisan::call('down', ['--secret' => $secret, '--retry' => 30, '--refresh' => 20]);
                $cookie = MaintenanceModeBypassCookie::create($secret);
            }
            $job = $updater->advance($job);
        } catch (Throwable $e) {
            report($e);
            $updater->clearJob();
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');
            }

            return redirect()->route('admin.system')->with('error', 'The update was not installed, and the site is as it was: '.$e->getMessage());
        }

        $labels = ['download' => 'Downloading the new version', 'compare' => 'Checking which files changed', 'write' => 'Installing the new files', 'finish' => 'Finishing up'];
        $response = response()->view('admin.update-progress', [
            'percent' => $updater->percent($job),
            'label' => $labels[$job['phase']] ?? 'Updating',
            'next' => route('admin.system.progress'),
        ]);

        return $cookie ? $response->withCookie($cookie) : $response;
    }

    /** "Restore previous version": puts back the files the last update changed. */
    public function restore(Updater $updater): RedirectResponse|Response
    {
        Gate::authorize('admin.manage');
        $backup = $updater->lastBackup();
        if (! $backup || $backup['restored_at']) {
            return back()->with('error', 'There is no update to undo.');
        }

        $secret = Str::random(40);
        Artisan::call('down', ['--secret' => $secret, '--retry' => 30, '--refresh' => 20]);
        try {
            $updater->restore($backup['path']);
        } catch (Throwable $e) {
            report($e);
            Artisan::call('up');

            return back()->with('error', 'Could not restore: '.$e->getMessage());
        }
        $updater->saveJob(['phase' => 'finish', 'action' => 'restore', 'from' => $backup['to'], 'to' => $backup['from']]);

        return redirect()->route('admin.system.progress')->withCookie(MaintenanceModeBypassCookie::create($secret));
    }

    /** An update that was interrupted and not continued: undo what it wrote and bring the site back. */
    public function cancel(Request $request, Updater $updater): RedirectResponse
    {
        Gate::authorize('admin.manage');
        $job = $updater->job();
        if ($job && in_array($job['phase'], ['write', 'finish'], true) && ! empty($job['backup']) && ($job['action'] ?? 'update') === 'update') {
            $updater->restore($job['backup']);
        }
        $updater->clearJob();
        $this->clearCaches();
        if (app()->isDownForMaintenance()) {
            Artisan::call('up');
        }
        Audit::record('system.update_cancelled', null, null, ['phase' => $job['phase'] ?? null], $request->user()->id);

        return redirect()->route('admin.system')->with('status', 'The update was stopped and the site is as it was before.');
    }

    /** Last step, on the new code: database changes, fresh caches, site back up. */
    private function finishJob(Request $request, Updater $updater, array $job): RedirectResponse
    {
        $problem = null;
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            report($e);
            $problem = $e->getMessage();
        }
        $this->clearCaches();
        if (app()->isDownForMaintenance()) {
            Artisan::call('up');
        }
        $updater->clearJob();

        $action = $job['action'] ?? 'update';
        $changed = 0;
        $added = 0;
        if ($action === 'update' && ! empty($job['backup']) && is_file($job['backup'].'/manifest.json')) {
            $m = json_decode((string) file_get_contents($job['backup'].'/manifest.json'), true);
            $changed = count($m['changed'] ?? []);
            $added = count($m['added'] ?? []);
        }
        Setting::set(self::LAST_KEY, json_encode(['action' => $action, 'to' => $job['to'] ?? '', 'at' => now()->toIso8601String(), 'problem' => $problem]));
        Audit::record('system.'.$action, null, ['version' => $job['from'] ?? null], ['version' => $job['to'] ?? null, 'changed' => $changed, 'added' => $added, 'problem' => $problem], $request->user()->id);

        if ($problem !== null) {
            return redirect()->route('admin.system')->with('error', 'The new files are in place, but the database changes stopped: '.$problem.' You can press Restore previous version.');
        }

        return redirect()->route('admin.system')->with('status', $action === 'restore'
            ? 'The previous version, '.self::versionName((string) $job['to']).', is back.'
            : 'Updated to '.self::versionName((string) $job['to']).'. '.$changed.' file(s) replaced and '.$added.' added. Your settings, uploads and database were kept.');
    }

    private function clearCaches(): void
    {
        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $command) {
            try {
                Artisan::call($command);
            } catch (Throwable) {
                // A cache that cannot be cleared is not a reason to keep the site down.
            }
        }
    }

    /** "release-20261025-193500" as "Oct 25, 2026 3:35 pm" in the platform's time zone; other names are shown as they are. */
    public static function versionName(string $tag): string
    {
        if (! preg_match('/^release-(\d{4})(\d{2})(\d{2})-(\d{2})(\d{2})(\d{2})?$/', $tag, $m)) {
            return $tag;
        }

        return \Illuminate\Support\Carbon::create((int) $m[1], (int) $m[2], (int) $m[3], (int) $m[4], (int) $m[5], 0, 'UTC')
            ->timezone(config('app.timezone'))->format('M j, Y g:i a T');
    }
}
