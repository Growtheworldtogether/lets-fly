<?php

namespace App\Services;

use App\Models\Invite;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Getting back in as an administrator without a command line (shared hosting has none).
 * The proof of ownership is the file system: whoever can create storage/app/recovery.txt on the server, through the
 * host's file manager, may use /recover. Until that file exists, /recover is a 404.
 */
final class AdminRecovery
{
    public const MAX_AGE = 3600;

    public static function file(): string
    {
        return config('setup.recovery_file', storage_path('app/recovery.txt'));
    }

    /** The secret typed into the file, or null when recovery is off (no file, empty, too short or older than an hour). */
    public static function secret(): ?string
    {
        $f = self::file();
        if (! is_file($f) || time() - (int) @filemtime($f) > self::MAX_AGE) {
            return null;
        }
        $s = trim((string) @file_get_contents($f));

        return strlen($s) >= 12 ? $s : null;
    }

    public static function close(): void
    {
        @unlink(self::file());
    }

    /** Checks the secret. Five wrong tries from one address switch recovery off until the file is made again. */
    public static function check(string $given, string $ip): void
    {
        $key = 'recover:'.sha1($ip);
        $tries = (int) Cache::get($key, 0);
        $secret = self::secret();
        if ($secret === null || $tries >= 5) {
            self::close();
            throw ValidationException::withMessages(['secret' => 'Recovery is switched off. Make the file again to start over.']);
        }
        if (! hash_equals($secret, trim($given))) {
            Cache::put($key, $tries + 1, 900);
            throw ValidationException::withMessages(['secret' => 'That is not the text in the file.']);
        }
        Cache::forget($key);
    }

    /** A new invite code for an administrator. The old code stops working. Returns the code, shown once. */
    public static function newInvite(string $name, string $email, InviteService $invites): string
    {
        StaffDirectory::sync();
        $email = strtolower($email);
        $existing = Invite::where('kind', 'admin')->where('email', $email)->first();
        if ($existing) {
            $code = $invites->regenerate($existing);
            Audit::record('recovery.invite', $existing);
        } else {
            [$invite, $code] = $invites->create($name, $email, 'admin');
            Audit::record('recovery.invite', $invite);
        }

        return $code;
    }

    public static function newPassword(string $email, string $password): User
    {
        $user = User::where('role', 'admin')->where('email', strtolower($email))->first();
        if (! $user) {
            throw ValidationException::withMessages(['email' => 'No administrator has that email.']);
        }
        $user->forceFill(['password' => $password, 'failed_logins' => 0, 'locked_until' => null])->save();
        Audit::record('recovery.password', $user, null, null, $user->id);

        return $user;
    }
}
