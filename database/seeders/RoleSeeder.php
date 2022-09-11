    <?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::create(['name' => 'super-admin']);
        $permission = Permission::create(['name' => 'list numbers']);
        $permission->assignRole($role);

        $role = Role::create(['name' => 'admin']);

        $role = Role::create(['name' => 'sales-junior']);
        
        $role = Role::create(['name' => 'sales-senior']);

        $role = Role::create(['name' => 'tele-sale']);

        $role = Role::create(['name' => 'accountant']);

        $role = Role::create(['name' => 'sales-manager']);
    }
}
