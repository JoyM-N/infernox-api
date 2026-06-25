<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]
            ->forgetCachedPermissions();


        $permissions = [
            // Robot permissions
            'robots.view',
            'robots.create',
            'robots.update',
            'robots.delete',
            'robots.provision',

            // Telemetry permissions
            'telemetry.view',

            // Incident permissions
            'incidents.view',
            'incidents.update',

            // Command permissions
            'commands.send',
            'commands.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Super Admin — can do everything
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());
          //operator can view,control and command robots, but cannot provision or delete them.
        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->givePermissionTo([
            'robots.view',
            'robots.update',
            'telemetry.view',
            'incidents.view',
            'incidents.update',
            'commands.send',
            'commands.view',
        ]);

        // Viewer — read only access
        // Can see everything but cannot change anything
        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->givePermissionTo([
            'robots.view',
            'telemetry.view',
            'incidents.view',
            'commands.view',
        ]);

        $this->command->info('✅ Roles and permissions seeded successfully.');
        $this->command->table(
            ['Role', 'Permissions'],
            [
                ['super_admin', 'All permissions'],
                ['operator',    'View/control robots, manage incidents, send commands'],
                ['viewer',      'Read-only access'],
            ]
        );
    }
}