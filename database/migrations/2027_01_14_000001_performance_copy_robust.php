<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NEW_TITLE = 'Ready on the pad, quick to the sky';

    private const NEW_INTRO = 'Doors up, pilot in, and the vehicle lifts straight off its pad. Vertical lift is the most demanding part of any flight, so the rotors and battery are sized for it with margin to spare. Once the wing takes over, cruising needs only a fraction of that power, and the vehicle spends most of its flight in that efficient state.';

    /**
     * Catches databases where the earlier exact-match migration did not apply (different line endings or spacing).
     * It only acts while the heading is still the old one, so text an admin has rewritten is left alone.
     */
    public function up(): void
    {
        $body = DB::table('page_sections')->where('slug', 'performance')->value('body');
        if (! $body) {
            return;
        }
        $norm = str_replace(["\r\n", "\r"], "\n", $body);
        $parts = preg_split('/\n\s*\n/', trim($norm));
        if (stripos($parts[0] ?? '', 'quick to climb') === false) {
            return;
        }
        $parts[0] = self::NEW_TITLE;
        $parts[1] = self::NEW_INTRO;
        DB::table('page_sections')->where('slug', 'performance')->update(['body' => implode("\n\n", $parts)]);
    }

    public function down(): void {}
};
