<?php

namespace App\Services;

use App\Models\AdminProfile;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Writes the title presets in config/admin.php to the permission tables and assigns titles. */
final class StaffDirectory
{
    public static function sync(): void
    {
        $all = array_keys(config('admin.permissions'));
        foreach ($all as $name) {
            Permission::findOrCreate($name, 'web');
        }
        foreach (config('admin.titles') as $title => $perms) {
            // A Super Admin can grant branding and content editing to Managers from Settings.
            if ($title === 'Manager' && \App\Models\Setting::bool('managers_edit_site', false)) {
                $perms[] = 'site.edit';
            }
            Role::findOrCreate($title, 'web')->syncPermissions($perms === ['*'] ? $all : $perms);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** A Super Admin's own actions, and actions taken while acting as a member, are never held for staff review. */
    public static function isSuperAdmin(User|int|null $user): bool
    {
        if ($user === null) {
            return false;
        }
        $user = $user instanceof User ? $user : User::find($user);

        return $user !== null && $user->hasRole('Super Admin');
    }

    /** The Super Admin who is acting as the member in this session ("log in as member"), if that is who it is. */
    public static function actingSuperAdmin(): ?User
    {
        $id = app()->bound('request') && request()->hasSession() ? request()->session()->get('impersonation.admin_id') : null;
        $admin = $id ? User::find($id) : null;

        return $admin && self::isSuperAdmin($admin) ? $admin : null;
    }

    public static function assign(User $user, string $title): void
    {
        self::sync();
        $user->forceFill(['role' => 'admin'])->save();
        AdminProfile::updateOrCreate(['user_id' => $user->id], ['title' => $title]);
        $user->syncRoles([$title]);
    }
}
