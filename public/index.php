<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Find the app. Normally this file sits in <app>/public. On shared hosting it may sit in public_html with the app
// next to it (../app), so both places are checked.
$base = null;
foreach ([__DIR__.'/..', __DIR__.'/../app'] as $candidate) {
    if (is_file($candidate.'/vendor/autoload.php')) {
        $base = $candidate;
        break;
    }
}
if ($base === null) {
    http_response_code(500);
    exit('The application folder was not found. Upload the app folder next to this one (or set the domain to point at app/public).');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $base.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $base.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
