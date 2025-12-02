<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Créer les permissions d'abord
        $permission1 = Permission::firstOrCreate([
            'name' => 'view own profile',
            'guard_name' => 'api'
        ]);

        $permission2 = Permission::firstOrCreate([
            'name' => 'edit own profile',
            'guard_name' => 'api'
        ]);

        // 2. Créer le rôle 'user'
        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api'
        ]);

        // 3. Assigner les permissions au rôle user
        $userRole->syncPermissions([$permission1, $permission2]);

        // 4. Créer d'autres permissions si nécessaire
        $otherPermissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
        ];

        foreach ($otherPermissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'api'
            ]);
        }

        // 5. Créer le rôle admin avec toutes les permissions
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api'
        ]);
        $adminRole->syncPermissions(Permission::all());

        echo "✅ Rôles et permissions créés avec succès!\n";
    }
}
