# Putting the platform on a cPanel host

## What you need
- PHP 8.3 or newer, with the zip, mbstring, gd, fileinfo, pdo_mysql and openssl extensions (cPanel: Select PHP Version).
- A MySQL database and user (cPanel: MySQL Databases).
- A way to run commands (cPanel: Terminal). Cron jobs are in cPanel too.

## 1. Build a release
On GitHub, open the repository, then Actions, Build release zip, Run workflow. When it finishes, the Releases page has two zips:
- `lets-fly-release.zip`: use it if you can point the domain at the `app/public` folder.
- `lets-fly-release-public_html.zip`: use it if the domain must use `public_html`. Extract it in your home folder.

## 2. Upload and extract
Upload the zip with the File Manager and extract it. The result is an `app` folder (and `public_html` for the second zip).

## 3. Settings
Copy `.env.example` to `.env` in the app folder and set:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
MAIL_*   (your SMTP2GO details)
```
Then, in Terminal inside the app folder:
```
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan invite:admin "Your Name" you@example.com
```
The last command prints your admin invite code once. Open the site, enter it, and set your password.

## 4. Reminders (cron)
Add one cron job that runs every minute:
```
* * * * * /usr/local/bin/php /home/USERNAME/app/artisan schedule:run >/dev/null 2>&1
```
It sends the day 7 and day 9 balance reminders. Check the PHP path with your host if that one is wrong.

## 5. Updates later
Build a new release on GitHub, then open Admin, System, and press Check for updates and Install update. Settings, uploads and the database are kept.

## Upload limits
Videos can be up to 250 MB in the app, but PHP limits uploads on its own. In cPanel (Select PHP Version, Options) raise `upload_max_filesize` and `post_max_size`.

## Lost admin code or password
- New admin code: `php artisan invite:admin "Name" email` (replaces the old code).
- New admin password: `php artisan admin:password email`.
