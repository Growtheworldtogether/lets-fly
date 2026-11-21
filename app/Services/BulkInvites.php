<?php

namespace App\Services;

use App\Models\Invite;
use App\Models\User;

/** Turns a pasted list (one person per line) into invites. Lines may be "Name" or "Name, email@example.com". */
final class BulkInvites
{
    public const MAX = 200;

    /**
     * @return array{rows: list<array{name: string, email: ?string, status: string, note: string}>, tooMany: bool}
     *   status: ok | known (name already invited, or repeated in this list; can be included on purpose) | bad (cannot be created)
     */
    public static function parse(string $text, bool $includeKnown = false): array
    {
        $lines = array_values(array_filter(array_map(fn ($l) => trim($l), preg_split('/\R/u', $text) ?: []), fn ($l) => $l !== ''));
        $tooMany = count($lines) > self::MAX;
        $lines = array_slice($lines, 0, self::MAX);

        $names = Invite::where('status', '!=', 'revoked')->pluck('name')->map(fn ($n) => mb_strtolower($n))->flip();
        $emails = User::pluck('email')->merge(Invite::whereNotNull('email')->pluck('email'))->map(fn ($e) => mb_strtolower($e))->flip();

        $seenNames = [];
        $seenEmails = [];
        $rows = [];
        foreach ($lines as $line) {
            [$name, $email] = self::split($line);
            $row = ['name' => $name, 'email' => $email, 'status' => 'ok', 'note' => ''];
            $key = mb_strtolower($name);

            if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
                $row['status'] = 'bad';
                $row['note'] = 'The name must be 2 to 120 characters.';
            } elseif ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $row['status'] = 'bad';
                $row['note'] = 'That email address does not look right.';
            } elseif ($email !== null && (isset($emails[mb_strtolower($email)]) || isset($seenEmails[mb_strtolower($email)]))) {
                $row['status'] = 'bad';
                $row['note'] = 'That email already has an account or an invite.';
            } elseif (isset($names[$key])) {
                $row['status'] = 'known';
                $row['note'] = 'This name already has an invite.';
            } elseif (isset($seenNames[$key])) {
                $row['status'] = 'known';
                $row['note'] = 'This name is in your list more than once.';
            }
            if ($row['status'] !== 'bad') {
                $seenNames[$key] = true;
                if ($email !== null) {
                    $seenEmails[mb_strtolower($email)] = true;
                }
            }
            $row['create'] = $row['status'] === 'ok' || ($row['status'] === 'known' && $includeKnown);
            $rows[] = $row;
        }

        return ['rows' => $rows, 'tooMany' => $tooMany];
    }

    /** @return array{0: string, 1: ?string} name and optional email; a tab or the last comma may separate them */
    private static function split(string $line): array
    {
        $line = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/u', '', $line) ?? '';
        $email = null;
        if (str_contains($line, "\t")) {
            $parts = array_map('trim', explode("\t", $line));
            $name = $parts[0];
            $email = collect(array_slice($parts, 1))->first(fn ($p) => str_contains($p, '@'));
        } elseif (($i = strrpos($line, ',')) !== false && str_contains(substr($line, $i + 1), '@')) {
            $name = trim(substr($line, 0, $i));
            $email = trim(substr($line, $i + 1));
        } else {
            $name = $line;
        }

        return [trim((string) preg_replace('/\s+/u', ' ', $name)), $email !== null && $email !== '' ? $email : null];
    }
}
