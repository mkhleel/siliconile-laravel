<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Admin;
use Spatie\Permission\Models\Role;

class CoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $this->call([]);
        // add roles some roles for admin guard
        $roles = [
            'staff' => 'Staff',
            'admin' => 'Admin',
            'editor' => 'Editor',
        ];
        foreach ($roles as $name => $displayName) {
            Role::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'admin']
            );
        }

        // add Admin User
        $adminUser = Admin::firstOrCreate(
            ['email' => 'm5lil@live.com'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => bcrypt('123123123'),
            ]
        );
        $adminUser->assignRole('admin');
    }
}
