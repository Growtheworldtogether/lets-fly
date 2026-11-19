<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/** The two uploaded logos: 'site' (the header, on every page) and 'vehicle' (stands in for the vehicle name title). */
final class Logos
{
    public const ROLES = ['site' => 'Site logo', 'vehicle' => 'Vehicle name logo'];

    private static array $cache = [];

    public static function media(string $role): ?Media
    {
        try {
            if (! isset(self::ROLES[$role]) || ! Schema::hasTable('settings')) {
                return null;
            }

            return self::$cache[$role] ??= (($id = Setting::find('logo_'.$role)?->value) ? (Media::find((int) $id) ?: false) : false) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function url(string $role): ?string
    {
        $m = self::media($role);

        return $m ? route('logo', $m->filename, false) : null;
    }

    public function store(string $role, UploadedFile $file, ?User $by = null): Media
    {
        abort_unless(isset(self::ROLES[$role]), 404);
        $media = app(MediaStore::class)->store($file, 'logo', brand('name').' '.strtolower(self::ROLES[$role]), $by);
        $this->remove($role);
        Setting::set('logo_'.$role, $media->id);
        self::$cache = [];

        return $media;
    }

    public function remove(string $role): void
    {
        if ($old = self::media($role)) {
            Storage::disk('local')->delete('media/'.$old->filename);
            $old->delete();
        }
        Setting::set('logo_'.$role, '');
        self::$cache = [];
    }

    /** Only a file that is currently set as a logo may be served without signing in. */
    public static function isLogoFile(string $filename): bool
    {
        foreach (array_keys(self::ROLES) as $role) {
            if (self::media($role)?->filename === $filename) {
                return true;
            }
        }

        return false;
    }
}
