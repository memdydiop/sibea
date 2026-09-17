<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view_dashboard',
            'manage_sectors',
            'manage_expertises',
            'manage_services',
            'manage_projects',
            'manage_leads',
            'manage_users',
            'manage_roles',
            'view_sectors',
            'view_projects',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $matrix = [
            'Super administrateur' => $permissions,
            'Administrateur' => ['view_dashboard', 'manage_sectors', 'manage_expertises', 'manage_services', 'manage_projects', 'manage_leads', 'manage_users', 'view_sectors', 'view_projects'],
            'Éditeur' => ['view_dashboard', 'manage_sectors', 'manage_expertises', 'manage_services', 'manage_projects', 'view_sectors', 'view_projects'],
            'Commercial' => ['view_dashboard', 'manage_leads', 'view_sectors', 'view_projects'],
        ];

        foreach ($matrix as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
