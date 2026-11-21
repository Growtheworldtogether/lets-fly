# Invite-Only Flying Vehicle Reservation Platform

School project. Laravel 13, MySQL, vanilla JS. Invite code is the only way in.

Setup: `composer install`, then open the site in a browser. A fresh copy opens the setup wizard at `/setup` (owner key, server check, site address, database, email and sender name, updates token, first administrator), which writes `.env`, creates the tables and ends by showing the administrator's invite code once. From another computer the wizard asks for the 8-character key in `storage/app/install-key.txt`. After it finishes, `/setup` answers 404. Lost admin code or password on a host with no terminal: see "Lost admin code or password" in docs/DEPLOY.md (a recovery file plus `/recover`). To set up from the command line instead: copy `.env.example` to `.env`, `php artisan key:generate`, `php artisan migrate`, then `php artisan invite:admin "Name" email@example.com`.

Local development uses SQLite (database/database.sqlite). Production will use MySQL.
