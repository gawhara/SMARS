<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@smars.local')->value('id');
        $companies = Company::pluck('id', 'code');            // code => id
        $branches = Branch::get()->groupBy('company_id');     // company_id => [branches]
        $departments = Department::pluck('id')->all();
        $positions = Position::pluck('id')->all();
        $shifts = Shift::pluck('id')->all();
        $ibanCodes = Bank::pluck('iban_code', 'code');        // e.g. RAJHI => 80

        // Names deliberately matched to nationality (ISO2). SA => Saudi (national id
        // starts with 1); everyone else is on an Iqama (starts with 2).
        $people = [
            ['co' => 'AMNIAT',           'ar' => 'محمد عبدالله العتيبي', 'en' => 'Mohammed Abdullah Al-Otaibi', 'nat' => 'SA', 'g' => 'male',   'bank' => 'RAJHI',   'job' => 'HR Manager'],
            ['co' => 'AMNIAT',           'ar' => 'أحمد حسن محمود',        'en' => 'Ahmed Hassan Mahmoud',        'nat' => 'EG', 'g' => 'male',   'bank' => 'RIYAD',   'job' => 'Accountant'],
            ['co' => 'AMNIAT',           'ar' => 'راجيش كومار',           'en' => 'Rajesh Kumar',                'nat' => 'IN', 'g' => 'male',   'bank' => 'ALBILAD', 'job' => 'Technician'],
            ['co' => 'AMNIAT_FACTORY',   'ar' => 'محمد أسلم خان',         'en' => 'Muhammad Aslam Khan',         'nat' => 'PK', 'g' => 'male',   'bank' => 'RAJHI',   'job' => 'Technician'],
            ['co' => 'AMNIAT_FACTORY',   'ar' => 'عبدالرحمن حسين',        'en' => 'Abdur Rahman Hossain',        'nat' => 'BD', 'g' => 'male',   'bank' => 'RIYAD',   'job' => 'Technician'],
            ['co' => 'AMNIAT_FACTORY',   'ar' => 'منى إبراهيم علي',       'en' => 'Mona Ibrahim Ali',            'nat' => 'EG', 'g' => 'female', 'bank' => 'ALBILAD', 'job' => 'Sales Executive'],
            ['co' => 'PTC',              'ar' => 'سارة فهد القحطاني',     'en' => 'Sara Fahad Al-Qahtani',       'nat' => 'SA', 'g' => 'female', 'bank' => 'RAJHI',   'job' => 'Branch Manager'],
            ['co' => 'PTC',              'ar' => 'خوسيه سانتوس',          'en' => 'Jose Santos',                 'nat' => 'PH', 'g' => 'male',   'bank' => 'RIYAD',   'job' => 'Engineer'],
            ['co' => 'PTC',              'ar' => 'عمر خالد الحلبي',       'en' => 'Omar Khaled Al-Halabi',       'nat' => 'SY', 'g' => 'male',   'bank' => 'ALBILAD', 'job' => 'Sales Executive'],
            ['co' => 'PTC_CONSTRUCTION', 'ar' => 'أنيل شارما',            'en' => 'Anil Sharma',                 'nat' => 'IN', 'g' => 'male',   'bank' => 'RAJHI',   'job' => 'Engineer'],
            ['co' => 'PTC_CONSTRUCTION', 'ar' => 'عمران خان',             'en' => 'Imran Khan',                  'nat' => 'PK', 'g' => 'male',   'bank' => 'RIYAD',   'job' => 'Technician'],
            ['co' => 'PTC_CONSTRUCTION', 'ar' => 'ليث العمري',            'en' => 'Laith Al-Omari',              'nat' => 'JO', 'g' => 'male',   'bank' => 'ALBILAD', 'job' => 'Accountant'],
        ];

        $domains = [
            'AMNIAT' => 'amniat.sa',
            'AMNIAT_FACTORY' => 'amniatfactory.sa',
            'PTC' => 'ptc.sa',
            'PTC_CONSTRUCTION' => 'ptcconstruction.sa',
        ];

        foreach ($people as $i => $p) {
            $n = $i + 1;
            $isSaudi = $p['nat'] === 'SA';
            $companyId = $companies[$p['co']];
            $branchId = optional($branches->get($companyId))->first()?->id;

            // Salary — kept so the monthly total never exceeds 4000.
            $basic = 2000 + ($i % 5) * 180;                 // 2000..2720
            $housing = round($basic * 0.2, 2);             // ~400..544
            $transport = 200.00;
            $other = 100.00;
            $overtime = ($i % 3) * 50;                     // 0/50/100
            $total = round($basic + $housing + $transport + $other + $overtime, 2); // <= ~3664

            $social = $isSaudi ? round($basic * 0.0975, 2) : 0.0; // GOSI employee share
            $loans = ($i % 3) * 100;
            $totalDeductions = round($social + $loans, 2);
            $remaining = round($total - $totalDeductions, 2);

            $transfer = ['al_rajhi_transfer' => 0, 'bank_albilad_transfer' => 0, 'riyad_bank_transfer' => 0, 'cash' => 0];
            $transfer[match ($p['bank']) {
                'RAJHI' => 'al_rajhi_transfer',
                'ALBILAD' => 'bank_albilad_transfer',
                'RIYAD' => 'riyad_bank_transfer',
                default => 'cash',
            }] = $remaining;

            $emailLocal = strtolower(str_replace([' ', "'"], ['.', ''], $p['en']));
            $account = str_pad((string) (100000000000000000 + $n), 18, '0', STR_PAD_LEFT);
            $iban = $this->saudiIban($ibanCodes[$p['bank']], $account);

            $attrs = [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'department_id' => $departments[$i % count($departments)] ?? null,
                'position_id' => $positions[$i % count($positions)] ?? null,
                'shift_id' => $shifts[$i % count($shifts)] ?? null,

                'name_ar' => $p['ar'],
                'name_en' => $p['en'],
                'email' => $emailLocal.'@'.$domains[$p['co']],
                'phone' => '+9665'.str_pad((string) (10000000 + $n), 8, '0', STR_PAD_LEFT),
                'phone_2' => '+9665'.str_pad((string) (20000000 + $n), 8, '0', STR_PAD_LEFT),
                'status' => 'active',

                'financial_employee_id' => 'FIN-'.(1000 + $n),
                'hr_employee_id' => 'HR-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
                'national_id' => ($isSaudi ? '1' : '2').str_pad((string) (345678900 + $n), 9, '0', STR_PAD_LEFT),

                'iqama_full_name_arabic' => $p['ar'],
                'iqama_full_name_english' => $p['en'],
                'full_name_arabic' => $p['ar'],
                'full_name_english' => $p['en'],
                'nationality' => $p['nat'],
                'saudi_non_saudi' => $isSaudi ? 'saudi' : 'non_saudi',
                'gender' => $p['g'],
                'birth_date' => Carbon::create(1985 + ($i % 12), ($i % 12) + 1, ($i % 27) + 1)->toDateString(),
                'iqama_expiry' => now()->addMonths(6 + $i)->toDateString(),

                'passport_id' => 'P'.str_pad((string) (1000000 + $n), 8, '0', STR_PAD_LEFT),
                'passport_full_name_arabic' => $p['ar'],
                'passport_full_name_english' => $p['en'],
                'passport_expiry' => now()->addYears(2)->addMonths($i)->toDateString(),

                'job_title' => $p['job'],
                'contract_type' => 'permanent',
                'start_date' => now()->subYears(3)->addMonths($i)->toDateString(),
                'end_date' => now()->addYears(1)->addMonths($i)->toDateString(),

                'bank' => $p['bank'],
                'iban' => $iban,
                'branch' => 'الرياض',

                'basic_salary' => $basic,
                'overtime' => $overtime,
                'housing_allowance' => $housing,
                'other_allowances' => $other,
                'transportation_allowance' => $transport,
                'training_labor_wages' => 0,
                'previous_dues' => 0,
                'total' => $total,

                'basic_salary_gosi' => $isSaudi ? $basic : 0,
                'housing_allowance_gosi' => $isSaudi ? $housing : 0,
                'other_gosi_items' => 0,
                'diff_registered_housing_allowance' => 0,

                'absence_deduction' => 0,
                'delay_deduction' => 0,
                'leave_deduction' => 0,
                'warnings_penalties' => 0,
                'insurance_deduction' => 0,
                'loans' => $loans,
                'social_insurance_saudi' => $social,
                'total_deductions' => $totalDeductions,

                'cash' => $transfer['cash'],
                'al_rajhi_transfer' => $transfer['al_rajhi_transfer'],
                'bank_albilad_transfer' => $transfer['bank_albilad_transfer'],
                'riyad_bank_transfer' => $transfer['riyad_bank_transfer'],
                'remaining_salary' => $remaining,

                'employment_status' => 'active',
            ];

            $employee = Employee::updateOrCreate(
                ['employee_code' => 'EMP-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT)],
                $attrs,
            );

            $employee->forceFill(['created_by' => $admin, 'updated_by' => $admin])->saveQuietly();
        }
    }

    /**
     * Build a checksum-valid Saudi IBAN: SA + check digits + 2-digit bank code + 18-digit account.
     */
    private function saudiIban(string $bankCode, string $account): string
    {
        $bban = $bankCode.$account;              // 20 chars
        $rearranged = $bban.'SA00';              // move country + placeholder to the end

        $numeric = '';
        foreach (str_split($rearranged) as $ch) {
            $numeric .= ctype_alpha($ch) ? (string) (ord(strtoupper($ch)) - 55) : $ch;
        }

        $mod = 0;
        foreach (str_split($numeric) as $d) {
            $mod = ($mod * 10 + (int) $d) % 97;
        }

        $check = 98 - $mod;

        return 'SA'.str_pad((string) $check, 2, '0', STR_PAD_LEFT).$bban;
    }
}
