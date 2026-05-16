<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'view users',
            'manage users',
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            'view stock movements',
            'create stock movements',
            'edit stock movements',
            'delete stock movements',
            'view reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // create roles and assign existing permissions

        // Super Admin role
        $roleSuperAdmin = Role::create(['name' => 'Super Admin']);
        $roleSuperAdmin->givePermissionTo(Permission::all());

        // Manager role
        $roleManager = Role::create(['name' => 'Manager']);
        $roleManager->givePermissionTo(Permission::all());

        // Kepala Gudang role
        $roleKepalaGudang = Role::create(['name' => 'Kepala Gudang']);
        $roleKepalaGudang->givePermissionTo([
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            'view stock movements',
            'create stock movements',
            'edit stock movements',
            'delete stock movements',
            'view reports',
        ]);

        // Operator Gudang role
        $roleOperatorGudang = Role::create(['name' => 'Operator Gudang']);
        $roleOperatorGudang->givePermissionTo([
            'view products',
            'view categories',
            'view stock movements',
            'create stock movements',
        ]);
    }
}
