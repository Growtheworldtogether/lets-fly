<?php

use Illuminate\Support\Facades\Schedule;

// Needs the server cron entry: * * * * * php /path/to/artisan schedule:run
Schedule::command('reminders:send')->dailyAt('09:00')->timezone('America/New_York');
