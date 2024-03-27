<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Policy;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'admin@admin.com')->first();
        $tenant = User::where('email', 'tenant@tenant.com')->first();
        $company = User::where('email', 'company@company.com')->first();
        $policy = Policy::where('name', 'firstPOlicy')->first();
        $employee = User::where('email', 'employee@employee.com')->first();

        $permissions = [

            'role.list',
            'role.create',
            'role.store',
            'role.edit',
            'role.update',
            'role.delete',

            'permission.list',
            'permission.create',
            'permission.store',
            'permission.edit',
            'permission.update',
            'permission.delete',

            'tenant.list',
            'tenant.create',
            'tenant.store',
            'tenant.edit',
            'tenant.update',
            'tenant.delete',

            'user.list',
            'user.create',
            'user.store',
            'user.edit',
            'user.update',
            'user.delete',

            'company.list',
            'company.create',
            'company.store',
            'company.edit',
            'company.update',
            'company.delete',

            'employee.list',
            'employee.create',
            'employee.store',
            'employee.edit',
            'employee.update',
            'employee.delete',

            'policy.list',
            'policy.create',
            'policy.store',
            'policy.edit',
            'policy.update',
            'policy.delete',

            'equipment.list',
            'equipment.create',
            'equipment.store',
            'equipment.edit',
            'equipment.update',
            'equipment.delete',

            'designation.list',
            'designation.create',
            'designation.store',
            'designation.edit',
            'designation.update',
            'designation.delete',

            'duty.list',
            'duty.create',
            'duty.store',
            'duty.edit',
            'duty.update',
            'duty.delete',

            'attendance.list',
            'attendance.store',
            'attendance.edit',
            'attendance.update',

            'attendance-report.list',

            'attendance-roster.list',
            'attendance-roster.create',
            'attendance-roster.store',
            'attendance-roster.edit',
            'attendance-roster.update',
            'attendance-roster.delete',

            'dashboard.list',

            'employee-transfer.list',
            'employee-transfer.store',

            'holiday.list',
            'holiday.create',
            'holiday.store',
            'holiday.edit',
            'holiday.update',
            'holiday.delete',

            'resignation.list',
            'resignation.create',
            'resignation.store',
            'resignation.edit',
            'resignation.update',
            'resignation.delete',

            'company-report.list',

            'subscription_plan.list',
            'subscription_plan.create',
            'subscription_plan.store',
            'subscription_plan.update',
            'subscription_plan.edit',
            'subscription_plan.delete',

            'subscriber.list',
            'subscriber.create',
            'subscriber.store',
            'subscriber.update',
            'subscriber.edit',
            'subscriber.delete',

            'leave.list',
            'leave.create',
            'leave.store',
            'leave.update',
            'leave.edit',
            'leave.delete',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            $this->createpermissionifnotexits($permission, 'sanctum');
        }
        $adminRole = Role::where(['name' => 'admin', 'guard_name' => 'sanctum'])->first();
        if (!$adminRole) {
            $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        }

        if (!$tenant) {
            $user1 = new User();
            $user1->email = 'tenant@tenant.com';
            $user1->password = bcrypt('12345678'); // Use bcrypt to hash the password
            $user1->status = '1';
            $user1->save();

            $tenant = new Tenant();
            $tenant->name = 'newtenant';
            $tenant->status = '1';
            $tenant->user_id = $user1->id;
            $tenant->save();

            $tenantRole = Role::where(['name' => 'tenant', 'guard_name' => 'sanctum'])->first();
            if (!$tenantRole) {
                $tenantRole = Role::create(['name' => 'tenant', 'guard_name' => 'sanctum']);
            }
            // get the next 63 permissions and assign to the role tenant
            $nextpermissions = Permission::skip(24)->take(999999)->get();

            $tenantRole->syncPermissions($nextpermissions);
            $user1->assignRole($tenantRole);
        }
        if (!$company) {
            $user2 = new User();
            $user2->email = 'company@company.com';
            $user2->password = bcrypt('12345678'); // Use bcrypt to hash the password
            $user2->status = '1';
            $user2->save();

            $company = new Company();
            $company->name = 'newcompany';
            $company->tenant_id = $tenant->id;
            $company->status = '1';
            $company->user_id = $user2->id;
            $company->save();

            $companyRole = Role::where(['name' => 'company', 'guard_name' => 'sanctum'])->first();
            if (!$companyRole) {
                $companyRole = Role::create(['name' => 'company', 'guard_name' => 'sanctum']);
            }
        }

        if (!$policy) {
            $policy = new Policy();
            $policy->name = 'firstPOlicy';
            $policy->tenant_id = $tenant->id;
            $policy->status = '1';
            $policy->shift_start = '10:00 PM';
            $policy->shift_end = '10:00 PM';
            $policy->status = '1';
            $policy->save();
        }

        if (!$employee) {
            $user3 = new User();
            $user3->email = 'employee@employee.com';
            $user3->password = bcrypt('12345678'); // Use bcrypt to hash the password
            $user3->status = '1';
            $user3->save();

            $employee = new Employee();
            $employee->name = 'new employee';
            $employee->user_id = $user3->id;
            $employee->tenant_id = $tenant->id;
            $employee->status = '1';
            $employee->save();

            $employeeRole = Role::where(['name' => 'employee', 'guard_name' => 'sanctum'])->first();
            if (!$employeeRole) {
                $employeeRole = Role::create(['name' => 'employee', 'guard_name' => 'sanctum']);
            }
        }

        if (!$user) {
            $user = new User();
            $user->email = 'admin@admin.com';
            $user->password = bcrypt('12345678'); // Use bcrypt to hash the password
            $user->status = '1';
            $user->save();
            // Get 18 permissions and assign them to the admin role
            $permissions = Permission::take(24)->get();
            $adminRole->syncPermissions($permissions);

            // Assign admin role to user
            $user->assignRole($adminRole);
        }
    }

    /**
     * Create a new user type.
     *
     * @param int $userId
     *
     * @return void
     */
    // private function createTenant(int $userId)
    // {
    //     $newtenant = new Tenant;
    //     $newtenant->name = 'newtenant';
    //     $newtenant->user_id = $userId;
    //     $newtenant->save();
    // }

    private function createpermissionifnotexits($name, $guardname)
    {
        if (!Permission::where('name', $name)->where('guard_name', $guardname)->exists()) {
            Permission::create(['name' => $name, 'guard_name' => $guardname]);
        }
    }
}
