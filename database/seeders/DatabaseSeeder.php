<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Models\Role;
use App\Models\Shift;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin', 'display_name_ar' => 'مدير النظام', 'display_name_en' => 'Super Admin'],
            ['name' => 'hr_manager', 'display_name_ar' => 'مدير الموارد البشرية', 'display_name_en' => 'HR Manager'],
            ['name' => 'hr_officer', 'display_name_ar' => 'مسؤول الموارد البشرية', 'display_name_en' => 'HR Officer'],
            ['name' => 'accountant', 'display_name_ar' => 'محاسب', 'display_name_en' => 'Accountant'],
            ['name' => 'branch_manager', 'display_name_ar' => 'مدير فرع', 'display_name_en' => 'Branch Manager'],
            ['name' => 'employee_viewer', 'display_name_ar' => 'مستعرض الموظفين', 'display_name_en' => 'Employee Viewer'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role + ['is_active' => true]);
        }

        // RBAC permission catalogue + default role grants.
        $this->call(PermissionSeeder::class);

        $companies = [
            ['code' => 'AMNIAT', 'name_en' => 'AMNIAT', 'name_ar' => 'أمنيات'],
            ['code' => 'AMNIAT_FACTORY', 'name_en' => 'AMNIAT FACTORY', 'name_ar' => 'مصنع أمنيات للصناعة'],
            ['code' => 'PTC', 'name_en' => 'PTC', 'name_ar' => 'تقنيات الدهان للتجارة'],
            ['code' => 'PTC_CONSTRUCTION', 'name_en' => 'PTC Construction', 'name_ar' => 'تقنيات الدهان للمقاولات'],
        ];

        foreach ($companies as $company) {
            Company::updateOrCreate(['code' => $company['code']], $company + ['is_active' => true]);
        }

        $branches = [
            ['company_code' => 'AMNIAT', 'name_en' => 'AMNIAT Head Office', 'name_ar' => 'المركز الرئيسي أمنيات', 'location' => 'Riyadh'],
            ['company_code' => 'AMNIAT_FACTORY', 'name_en' => 'AMNIAT Factory Plant', 'name_ar' => 'فرع مصنع أمنيات', 'location' => 'Industrial Area'],
            ['company_code' => 'PTC', 'name_en' => 'PTC Trading Office', 'name_ar' => 'فرع تقنيات الدهان للتجارة', 'location' => 'Riyadh'],
            ['company_code' => 'PTC_CONSTRUCTION', 'name_en' => 'PTC Construction Office', 'name_ar' => 'فرع تقنيات الدهان للمقاولات', 'location' => 'Riyadh'],
        ];

        foreach ($branches as $branch) {
            $companyId = Company::where('code', $branch['company_code'])->value('id');

            Branch::updateOrCreate(
                ['company_id' => $companyId, 'name_en' => $branch['name_en']],
                [
                    'name_ar' => $branch['name_ar'],
                    'location' => $branch['location'],
                    'is_active' => true,
                ],
            );
        }

        $departments = [
            ['name_en' => 'Human Resources', 'name_ar' => 'الموارد البشرية'],
            ['name_en' => 'Finance', 'name_ar' => 'المالية'],
            ['name_en' => 'Sales', 'name_ar' => 'المبيعات'],
            ['name_en' => 'Information Technology', 'name_ar' => 'تقنية المعلومات'],
            ['name_en' => 'Operations', 'name_ar' => 'العمليات'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(['name_en' => $department['name_en']], $department + ['is_active' => true]);
        }

        $positions = [
            ['name_en' => 'HR Manager', 'name_ar' => 'مدير الموارد البشرية'],
            ['name_en' => 'Accountant', 'name_ar' => 'محاسب'],
            ['name_en' => 'Sales Executive', 'name_ar' => 'مسؤول مبيعات'],
            ['name_en' => 'Technician', 'name_ar' => 'فني'],
            ['name_en' => 'Engineer', 'name_ar' => 'مهندس'],
            ['name_en' => 'Branch Manager', 'name_ar' => 'مدير فرع'],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(['name_en' => $position['name_en']], $position + ['is_active' => true]);
        }

        $shifts = [
            ['name_en' => 'Morning Shift', 'name_ar' => 'الوردية الصباحية', 'start_time' => '08:00', 'end_time' => '16:00'],
            ['name_en' => 'Evening Shift', 'name_ar' => 'الوردية المسائية', 'start_time' => '16:00', 'end_time' => '00:00'],
            ['name_en' => 'Night Shift', 'name_ar' => 'الوردية الليلية', 'start_time' => '00:00', 'end_time' => '08:00'],
        ];

        foreach ($shifts as $shift) {
            Shift::updateOrCreate(['name_en' => $shift['name_en']], $shift + ['is_active' => true]);
        }

        $settings = [
            ['key' => 'system.name', 'value' => 'SMARS', 'type' => 'string', 'group' => 'general'],
            ['key' => 'system.default_locale', 'value' => 'ar', 'type' => 'string', 'group' => 'language'],
            ['key' => 'system.fallback_locale', 'value' => 'en', 'type' => 'string', 'group' => 'language'],
            ['key' => 'system.timezone', 'value' => 'Asia/Riyadh', 'type' => 'string', 'group' => 'general'],
            ['key' => 'ui.font', 'value' => 'Cairo', 'type' => 'string', 'group' => 'appearance'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        User::updateOrCreate(
            ['email' => 'admin@smars.local'],
            [
                'role_id' => Role::where('name', 'super_admin')->value('id'),
                'name' => 'SMARS Admin',
                'password' => Hash::make('password'),
                'preferred_locale' => 'ar',
                'is_active' => true,
            ],
        );

        $this->call([
            BankSeeder::class,
            CountrySeeder::class,
        ]);
    }
}
