<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    public function boot(): void
    {
        RateLimiter::for('gate', fn ($r) => Limit::perMinute(8)->by($r->ip()));
        RateLimiter::for('signin', fn ($r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('portal', fn ($r) => Limit::perMinute(180)->by($r->user()?->id ?: $r->ip()));

        try {
            config(['app.name' => brand('name'), 'mail.from.name' => brand('sender_name')]);
        } catch (\Throwable) {
        }

        Blade::directive('money', fn ($expression) => "<?php echo e(\\App\\Support\\Money::cents((int) ({$expression}))->format()); ?>");
    }
}
