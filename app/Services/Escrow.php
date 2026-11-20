<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Setting;

/** The escrow account members wire to. Set by an admin; nothing is invoiced until it is complete. */
final class Escrow
{
    public const FIELDS = [
        'escrow_agent' => 'Escrow agent (independent fiduciary / escrow attorney)',
        'escrow_entity' => 'Escrow beneficiary (entity name)',
        'escrow_bank' => 'Bank name',
        'escrow_account' => 'Account number',
        'escrow_routing' => 'Routing number (ABA)',
        'escrow_swift' => 'SWIFT / BIC (international wires)',
        'escrow_bank_address' => 'Bank address',
    ];

    private const REQUIRED = ['escrow_agent', 'escrow_entity', 'escrow_bank', 'escrow_account'];

    public static function get(string $key): string
    {
        return trim((string) Setting::find($key)?->value);
    }

    public static function notes(): string
    {
        return trim((string) Setting::find('wire_details')?->value);
    }

    /** The agent's name, or a plain phrase until it is set. */
    public static function agent(): string
    {
        return self::get('escrow_agent') ?: 'an independent escrow agent';
    }

    /** True once the required details are filled in and no [bracketed placeholder] is left in any of them. */
    public static function ready(): bool
    {
        foreach (self::REQUIRED as $k) {
            if (self::get($k) === '') {
                return false;
            }
        }
        foreach ([...array_keys(self::FIELDS), 'wire_details'] as $k) {
            if (str_contains($k === 'wire_details' ? self::notes() : self::get($k), '[')) {
                return false;
            }
        }

        return true;
    }

    /** Label and value pairs for the wire block, ending with this reservation's own reference. */
    public static function lines(Reservation $r): array
    {
        $out = [];
        foreach (self::FIELDS as $key => $label) {
            if (($v = self::get($key)) !== '') {
                $out[] = [$key === 'escrow_agent' ? 'Escrow agent' : ($key === 'escrow_entity' ? 'Escrow beneficiary' : preg_replace('/ \(.*/', '', $label)), $v];
            }
        }
        $out[] = ['Reference', 'Allocation #'.$r->reference];

        return $out;
    }
}
