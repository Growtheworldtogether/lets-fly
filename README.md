# Invite-Only Flying Vehicle Reservation Platform

School project. Laravel 13, MySQL, vanilla JS. Invite code is the only way in.

Setup: `composer install`, copy `.env.example` to `.env`, `php artisan key:generate`, `php artisan migrate`, then create the first admin invite with `php artisan invite:admin "Name" email@example.com` (coming in the next step).

Local development uses SQLite (database/database.sqlite). Production will use MySQL.
