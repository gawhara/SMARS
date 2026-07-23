<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Populate the permission catalogue and apply the default role grants. Safe
     * to re-run: permissions are upserted and each role's grants are synced to
     * the catalogue defaults.
     */
    public function run(): void
    {
        foreach (PermissionCatalog::all() as $name => $group) {
            Permission::updateOrCreate(['name' => $name], ['group' => $group]);
        }

        $idByName = Permission::pluck('id', 'name');

        foreach (PermissionCatalog::roleDefaults() as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                continue;
            }

            $role->permissions()->sync($idByName->only($permissions)->values()->all());
        }
    }
}
