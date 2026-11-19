<?php

namespace App\Services;

use App\Models\Invite;
use App\Models\InviteEvent;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Creates, finds and records invite codes. A code is shown once; only its hash is kept. */
final class InviteService
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** @return array{0: Invite, 1: string} the invite and the code to show once */
    public function create(string $name, ?string $email, string $kind = 'member', ?string $label = null, ?User $by = null): array
    {
        $code = $this->makeCode();
        $invite = Invite::create([
            'code_lookup' => self::lookupHash($code),
            'code_hint' => substr(self::normalize($code), -4),
            'kind' => $kind,
            'name' => $name,
            'email' => $email ? strtolower($email) : null,
            'label' => $label,
            'created_by' => $by?->id,
        ]);
        $this->log('created', $invite, $by);

        return [$invite, $code];
    }

    /** A new code for the same person. The old one stops working. */
    public function regenerate(Invite $invite, ?User $by = null): string
    {
        $code = $this->makeCode();
        $invite->update([
            'code_lookup' => self::lookupHash($code),
            'code_hint' => substr(self::normalize($code), -4),
            'status' => $invite->user_id ? 'claimed' : 'active',
            'revoked_at' => null,
        ]);
        $this->log('regenerated', $invite, $by);

        return $code;
    }

    public function find(string $code): ?Invite
    {
        $n = self::normalize($code);
        if (strlen($n) < 12) {
            return null;
        }
        $invite = Invite::where('code_lookup', self::lookupHash($n))->first();

        return $invite && $invite->isUsable() ? $invite : null;
    }

    public function log(string $type, ?Invite $invite = null, ?User $user = null, ?int $reservationId = null): void
    {
        $r = app()->bound('request') ? request() : null;
        InviteEvent::create([
            'invite_id' => $invite?->id,
            'user_id' => $user?->id ?? $invite?->user_id,
            'reservation_id' => $reservationId,
            'type' => $type,
            'ip' => $r?->ip(),
            'user_agent' => $r ? substr((string) $r->userAgent(), 0, 500) : null,
        ]);
    }

    /** PA-000123: the prefix, digits and next number are admin settings. */
    public function nextMemberNumber(): string
    {
        return DB::transaction(function () {
            $next = (int) Setting::value('member_next', 1);
            Setting::set('member_next', $next + 1);
            $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', Setting::value('member_prefix', config('pioneer.member_prefix'))));
            $digits = max(3, (int) Setting::value('member_digits', config('pioneer.member_digits')));

            return $prefix.'-'.str_pad((string) $next, $digits, '0', STR_PAD_LEFT);
        });
    }

    public static function normalize(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    public static function lookupHash(string $code): string
    {
        return hash('sha256', self::normalize($code));
    }

    private function makeCode(): string
    {
        $out = '';
        for ($i = 0; $i < 16; $i++) {
            $out .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return implode('-', str_split($out, 4));
    }
}
