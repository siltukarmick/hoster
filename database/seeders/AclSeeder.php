<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AclSeeder extends Seeder
{
    /**
     * Seed the ACL system with default modules, roles, and an admin user.
     */
    public function run(): void
    {
        // 1. Create default modules
        $modules = [
            ['name' => 'dashboard',    'label' => 'Dashboard'],
            ['name' => 'users',        'label' => 'Users'],
            ['name' => 'tenants',      'label' => 'Tenants'],
            ['name' => 'employees',    'label' => 'Employees'],
            ['name' => 'roles',        'label' => 'Roles & Permissions'],
            ['name' => 'modules',      'label' => 'Modules'],
            ['name' => 'billing',      'label' => 'Billing'],
            ['name' => 'dns',          'label' => 'DNS Management'],
            ['name' => 'support',      'label' => 'Support Tickets'],
            ['name' => 'hosting',      'label' => 'Hosting Services'],
        ];

        foreach ($modules as $mod) {
            Module::firstOrCreate(['name' => $mod['name']], ['label' => $mod['label']]);
        }

        // 2. Create system admin role with full permissions
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'scope' => 'system', 'tenant_id' => null],
            ['name' => 'admin', 'scope' => 'system', 'tenant_id' => null]
        );

        foreach (Module::all() as $module) {
            $adminRole->permissions()->firstOrCreate(
                ['module_id' => $module->id],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true]
            );
        }

        // 3. Create a default system admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@hoster.test'],
            [
                'name'      => 'System Admin',
                'email'     => 'admin@hoster.test',
                'password'  => Hash::make('password'),
                'user_type' => 'system',
            ]
        );

        $adminUser->assignRole($adminRole);

        // 4. Create default tenant roles
        $tenantRoles = [
            'admin' => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'manager' => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'staff'   => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
        ];

        // These are created dynamically when a tenant registers; sample roles for reference
        $this->command->info('ACL seeded successfully!');
        $this->command->info('Admin login: admin@hoster.test / password');
    }
}