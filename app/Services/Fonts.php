<?php

namespace App\Services;

use App\Models\Font;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Uploaded fonts (.woff2 only) and which part of the site uses which one. */
final class Fonts
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    /** role => [label, CSS selectors it applies to] */
    public const ROLES = [
        'vehicle' => ['Vehicle name title', ['.v-name', '.v-hero h1']],
        'heading' => ['Titles and headings', [':is(h1,h2,h3,h4,.big):not(.v-name,.v-hero h1)']],
        'body' => ['Body text', ['']],
        'brand' => ['Company name in the header', ['.mark']],
        'ui' => ['Buttons, menu and labels', ['.btn', '.topnav a', '.drawer a', 'label', '.eyebrow', '.tag', 'button', 'th']],
    ];

    public function store(UploadedFile $file, string $label, ?User $by = null): Font
    {
        if (! $file->isValid() || $file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['font' => 'The font file must be 2 MB or smaller.']);
        }
        $data = (string) file_get_contents($file->getRealPath());
        // A real WOFF2 file starts with the signature "wOF2". Checked by content, never by the file name.
        if (! str_starts_with($data, 'wOF2') || ! str_ends_with(strtolower($file->getClientOriginalName()), '.woff2')) {
            throw ValidationException::withMessages(['font' => 'Upload a .woff2 font file.']);
        }
        $name = Str::random(40).'.woff2';
        Storage::disk('local')->put('fonts/'.$name, $data);

        return Font::create([
            'label' => Str::limit(trim(strip_tags($label)) ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 60, ''),
            'filename' => $name,
            'original_name' => Str::limit(preg_replace('/[^\w. -]+/', '', $file->getClientOriginalName()) ?: 'font.woff2', 120, ''),
            'size' => strlen($data),
            'uploaded_by' => $by?->id,
        ]);
    }

    public function delete(Font $font): void
    {
        foreach (array_keys(self::ROLES) as $role) {
            if (Setting::find('font_'.$role)?->value === (string) $font->id) {
                Setting::set('font_'.$role, '');
            }
        }
        Storage::disk('local')->delete('fonts/'.$font->filename);
        $font->delete();
    }

    /** Which font id (or null for the built-in one) each role uses. */
    public static function assignments(): array
    {
        $out = [];
        foreach (array_keys(self::ROLES) as $role) {
            $v = Setting::find('font_'.$role)?->value;
            $out[$role] = $v !== null && $v !== '' ? (int) $v : null;
        }

        return $out;
    }

    /** The <style> content for every page: @font-face for each uploaded font and the role rules. Empty when nothing is uploaded. */
    public static function css(bool $all = false): string
    {
        try {
            if (! Schema::hasTable('fonts')) {
                return '';
            }
            $fonts = Font::all()->keyBy('id');
            if ($fonts->isEmpty()) {
                return '';
            }
            $assigned = self::assignments();
            $css = '';
            foreach ($fonts as $f) {
                if ($all || in_array($f->id, $assigned, true)) {
                    $css .= '@font-face{font-family:"'.$f->family().'";src:url("'.route('font', $f->filename, false).'") format("woff2");font-weight:100 900;font-style:normal;font-display:swap}';
                }
            }
            foreach (self::ROLES as $role => [, $selectors]) {
                $f = $fonts->get($assigned[$role] ?? 0);
                if (! $f) {
                    continue;
                }
                $stack = '"'.$f->family().'",'.($role === 'brand' || $role === 'vehicle' ? 'sans-serif' : '"Figtree","Helvetica Neue",Arial,sans-serif');
                $list = array_map(fn ($s) => $s === '' ? 'html body, html body input, html body textarea, html body select' : 'html body '.$s, $selectors);
                $css .= implode(',', $list).'{font-family:'.$stack.'}';
            }

            return $css;
        } catch (\Throwable) {
            return '';
        }
    }
}
