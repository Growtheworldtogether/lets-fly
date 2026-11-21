# Putting the platform on a cPanel host

## What you need
- PHP 8.3 or newer, with the zip, mbstring, gd, fileinfo, pdo_mysql and openssl extensions (cPanel: Select PHP Version).
- A MySQL database and user (cPanel: MySQL Databases).
- No command line is needed. Setup runs in the browser. Cron jobs are in cPanel (Cron Jobs).

## 1. Build a release
On GitHub, open the repository, then Actions, Build release zip, Run workflow. When it finishes, the Releases page has two zips:
- `lets-fly-release.zip`: use it if you can point the domain at the `app/public` folder.
- `lets-fly-release-public_html.zip`: use it if the domain must use `public_html`. Extract it in your home folder.

## 2. Upload and extract
Upload the zip with the File Manager and extract it. The result is an `app` folder (and `public_html` for the second zip).

## 3. Run the setup wizard
Open your domain in a browser. A fresh copy opens the setup wizard by itself (no `.env` needed). It asks for:
1. **Owner key**: open `storage/app/install-key.txt` with the File Manager and type the 8 characters. (Skipped when you open the wizard on the server itself.)
2. **Server check**: PHP version, extensions and writable folders. Fix anything marked Missing in cPanel, then Check again.
3. **Site address** (`https://your-domain`) and time zone.
4. **Database**: choose MySQL and enter the host, database, user and password from cPanel (MySQL Databases). The connection is tested.
5. **Email**: your SMTP2GO details (tested), or choose later.
6. **First administrator**: your name and email.
7. **Install**: tick "This is a live site" and press Install now.

The wizard writes `.env`, creates the tables and shows your admin invite code once. Copy it, open the front page, enter the code and choose your password. After that, `/setup` answers 404.

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
No command line is needed. With the cPanel File Manager:
1. In the app folder, open `storage/app` and create a file named `recovery.txt`. Type any text of 12 or more characters in it (a long phrase of your own) and save.
2. Open `https://your-domain/recover`. Enter the same text, choose **A new invite code** (the old one stops working) or **A new password**, and enter the administrator's email.
3. The page shows the new code once, or confirms the new password. The file is removed automatically.

`/recover` answers 404 whenever the file is missing, so nobody can find it. It switches itself off after one use, after five wrong tries, or after an hour. If you prefer a terminal: `php artisan invite:admin "Name" email` and `php artisan admin:password email` do the same.
