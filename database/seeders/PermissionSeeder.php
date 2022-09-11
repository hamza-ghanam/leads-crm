<?php

namespace Database\Seeders;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /*
            $p = [
                'name' => 'edit ticket',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            Permission::insert($p);
            $permission = Permission::whereName('add ticket');

            $role = Role::whereName('super-admin')->first();

            $role->givePermissionTo($permission);

            $role = Role::whereName('admin')->first();

            $role->givePermissionTo($permission);
        */

        $permissions = [
            [
                'name' => 'list tickets',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'show ticket',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'add ticket',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'delete ticket',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'change status',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'review ticket',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'approve ticket',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'book ticket',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'create invoice',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'list users',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'show user',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'add user',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'delete user',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        Permission::insert($permissions);
        $permissions = Permission::all();

        $role = Role::whereName('super-admin')->first();

        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission);
        }

        $role = Role::whereName('admin')->first();

        foreach ($permissions as $permission) {
            if ($permission->name !== 'approve ticket' AND $permission->name !== 'book ticket' AND $permission->name !== 'create invoice')
                $role->givePermissionTo($permission);

        }

        $role = Role::whereName('sale')->first();

        foreach ($permissions as $key => $permission) {
            if (in_array($key, [0, 1, 4, 7]))
                $role->givePermissionTo($permission);
        }

        $role = Role::whereName('tele sale')->first();

        foreach ($permissions as $key => $permission) {
            if (in_array($key, [0, 1, 4, 7]))
                $role->givePermissionTo($permission);
        }

        $role = Role::whereName('accountant')->first();

        foreach ($permissions as $key => $permission) {
            if (in_array($key, [1, 8]))
                $role->givePermissionTo($permission);
        }

        $role = Role::whereName('sales-manager')->first();

        foreach ($permissions as $permission) {
            if ($permission->name !== 'approve ticket' AND $permission->name !== 'book ticket' AND $permission->name !== 'create invoice')
                $role->givePermissionTo($permission);

        }

    }
}
