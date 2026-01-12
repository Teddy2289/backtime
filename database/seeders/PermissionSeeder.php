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

        /*
        |--------------------------------------------------------------------------
        | 1. Permissions de base
        |--------------------------------------------------------------------------
        */
        $permission1 = Permission::firstOrCreate([
            'name' => 'view own profile',
            'guard_name' => 'api'
        ]);

        $permission2 = Permission::firstOrCreate([
            'name' => 'edit own profile',
            'guard_name' => 'api'
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. Rôle utilisateur classique
        |--------------------------------------------------------------------------
        */
        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api'
        ]);

        $userRole->syncPermissions([$permission1, $permission2]);

        /*
        |--------------------------------------------------------------------------
        | 3. Permissions administrateur global
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | 4. Permissions Chef d'équipe
        |--------------------------------------------------------------------------
        | assigner une tâche
        | créer une tâche
        | éditer une tâche
        | supprimer une tâche
        | créer un espace
        | supprimer un espace
        |--------------------------------------------------------------------------
        */
        $teamLeaderPermissions = [
            'assign task',
            'create task',
            'edit task',
            'delete task',
            'create space',
            'delete space',
        ];

        foreach ($teamLeaderPermissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'api'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Rôle Chef d'Équipe
        |--------------------------------------------------------------------------
        */
        $teamLeaderRole = Role::firstOrCreate([
            'name' => 'team_leader',
            'guard_name' => 'api'
        ]);

        $teamLeaderRole->syncPermissions($teamLeaderPermissions);

        /*
        |--------------------------------------------------------------------------
        | 6. Rôle Admin
        |--------------------------------------------------------------------------
        */
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api'
        ]);

        $adminRole->syncPermissions(Permission::all());

        echo "✅ Rôles & permissions créés avec succès (admin, user, team_leader) !\n";
    }
}
