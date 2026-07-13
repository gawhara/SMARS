<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MachineEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $hrIds = [
            '10006', '10007', '10008', '10009', '10010', '10011', '10013', '10014', '10021', '10051',
            '10073', '10075', '10108', '10125', '10173', '10205', '10220', '10251', '10252', '20001',
            '20006', '20007', '20008', '20015', '20031', '20033', '20039', '20049', '20068', '20075',
            '20076', '20087', '20088', '20097', '20106', '20120', '20125', '20134', '20138', '20149',
            '20152', '20205',
        ];

        // Names are deliberately paired with their nationalities.
        $people = [
            ['محمد عبدالله العتيبي', 'Mohammed Abdullah Al-Otaibi', 'SA', 'male'],
            ['سارة فهد القحطاني', 'Sara Fahad Al-Qahtani', 'SA', 'female'],
            ['خالد ناصر الحربي', 'Khalid Nasser Al-Harbi', 'SA', 'male'],
            ['نورة سعد الدوسري', 'Noura Saad Al-Dosari', 'SA', 'female'],
            ['عبدالعزيز صالح الغامدي', 'Abdulaziz Saleh Al-Ghamdi', 'SA', 'male'],
            ['ريم تركي المطيري', 'Reem Turki Al-Mutairi', 'SA', 'female'],
            ['فيصل أحمد الزهراني', 'Faisal Ahmed Al-Zahrani', 'SA', 'male'],
            ['هناء ماجد الشمري', 'Hana Majed Al-Shammari', 'SA', 'female'],
            ['سلمان إبراهيم العنزي', 'Salman Ibrahim Al-Enezi', 'SA', 'male'],
            ['لطيفة راشد السبيعي', 'Latifa Rashid Al-Subaie', 'SA', 'female'],
            ['تركي منصور الشهري', 'Turki Mansour Al-Shehri', 'SA', 'male'],
            ['أمل يوسف المالكي', 'Amal Yousef Al-Malki', 'SA', 'female'],
            ['بدر علي القحطاني', 'Bader Ali Al-Qahtani', 'SA', 'male'],
            ['مها عبدالله العتيبي', 'Maha Abdullah Al-Otaibi', 'SA', 'female'],
            ['أحمد حسن محمود', 'Ahmed Hassan Mahmoud', 'EG', 'male'],
            ['منى إبراهيم علي', 'Mona Ibrahim Ali', 'EG', 'female'],
            ['محمود السيد عبدالعال', 'Mahmoud El-Sayed Abdelal', 'EG', 'male'],
            ['نهى محمد مصطفى', 'Noha Mohamed Mostafa', 'EG', 'female'],
            ['راجش كومار', 'Rajesh Kumar', 'IN', 'male'],
            ['بريا شارما', 'Priya Sharma', 'IN', 'female'],
            ['أنيل باتيل', 'Anil Patel', 'IN', 'male'],
            ['سنيها ريدي', 'Sneha Reddy', 'IN', 'female'],
            ['محمد أسلم خان', 'Muhammad Aslam Khan', 'PK', 'male'],
            ['عائشة نور', 'Ayesha Noor', 'PK', 'female'],
            ['عمران أحمد', 'Imran Ahmed', 'PK', 'male'],
            ['فاطمة زهراء', 'Fatima Zahra', 'PK', 'female'],
            ['عبدالرحمن حسين', 'Abdur Rahman Hossain', 'BD', 'male'],
            ['نسيمة أختر', 'Nasima Akter', 'BD', 'female'],
            ['محمد كريم', 'Mohammad Karim', 'BD', 'male'],
            ['خوسيه سانتوس', 'Jose Santos', 'PH', 'male'],
            ['ماريا كروز', 'Maria Cruz', 'PH', 'female'],
            ['كارلو رييس', 'Carlo Reyes', 'PH', 'male'],
            ['ليث العمري', 'Laith Al-Omari', 'JO', 'male'],
            ['رنا الخطيب', 'Rana Al-Khatib', 'JO', 'female'],
            ['عمر خالد الحلبي', 'Omar Khaled Al-Halabi', 'SY', 'male'],
            ['لين أحمد المصري', 'Lynn Ahmad Al-Masri', 'SY', 'female'],
            ['مصطفى عثمان', 'Mustafa Osman', 'SD', 'male'],
            ['آلاء محمد إدريس', 'Alaa Mohamed Idris', 'SD', 'female'],
            ['عبدالله يحيى الصبري', 'Abdullah Yahya Al-Sabri', 'YE', 'male'],
            ['سمية علي الحكيمي', 'Somaya Ali Al-Hakimi', 'YE', 'female'],
            ['رام بهادور ثابا', 'Ram Bahadur Thapa', 'NP', 'male'],
            ['سيتا غورونغ', 'Sita Gurung', 'NP', 'female'],
        ];

        $companies = Company::query()->orderBy('id')->get();
        if ($companies->count() !== 4) {
            throw new \RuntimeException('Exactly four companies are required before seeding machine employees.');
        }

        $shifts = Shift::query()->where('shift_number', 1)->orderBy('id')->pluck('id')->values();

        DB::transaction(function () use ($hrIds, $people, $companies, $shifts): void {
            // Explicitly replace the current employee master data, including soft-deleted rows.
            DB::table('employees')->delete();

            foreach ($hrIds as $index => $hrId) {
                [$nameAr, $nameEn, $nationality, $gender] = $people[$index];
                $company = $companies[$index % $companies->count()];
                $branchId = Branch::query()->where('company_id', $company->id)->orderBy('id')->value('id');
                $isSaudi = $nationality === 'SA';

                $basic = 2200 + (($index * 125) % 700); // 2200–2875
                $housing = round($basic * 0.10, 2);
                $transport = 250.00;
                $other = 100.00;
                $total = round($basic + $housing + $transport + $other, 2); // Always below 4000.
                $gosi = $isSaudi ? round($basic * 0.0975, 2) : 0.00;
                $remaining = round($total - $gosi, 2);
                $serial = str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

                Employee::query()->create([
                    'company_id' => $company->id,
                    'branch_id' => $branchId,
                    'shift_id' => $shifts->isNotEmpty() ? $shifts[$index % $shifts->count()] : null,
                    'name_ar' => $nameAr,
                    'name_en' => $nameEn,
                    'full_name_arabic' => $nameAr,
                    'full_name_english' => $nameEn,
                    'iqama_full_name_arabic' => $nameAr,
                    'iqama_full_name_english' => $nameEn,
                    'passport_full_name_arabic' => $nameAr,
                    'passport_full_name_english' => $nameEn,
                    'email' => "employee.{$hrId}@example.sa",
                    'phone' => '+96655'.str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT),
                    'status' => 'active',
                    'employment_status' => 'active',
                    'employee_code' => 'EMP-'.$hrId,
                    'financial_employee_id' => 'FIN-'.$hrId,
                    'hr_employee_id' => $hrId,
                    'national_id' => ($isSaudi ? '1' : '2').str_pad((string) (450000000 + $index), 9, '0', STR_PAD_LEFT),
                    'nationality' => $nationality,
                    'saudi_non_saudi' => $isSaudi ? 'saudi' : 'non_saudi',
                    'gender' => $gender,
                    'birth_date' => now()->subYears(24 + ($index % 22))->subMonths($index % 12)->toDateString(),
                    'iqama_expiry' => $isSaudi ? null : now()->addMonths(8 + ($index % 18))->toDateString(),
                    'passport_id' => $nationality.$hrId.$serial,
                    'passport_expiry' => now()->addYears(2 + ($index % 4))->toDateString(),
                    'job_title' => ['Technician', 'Sales Representative', 'Accountant', 'Storekeeper', 'Administrator'][$index % 5],
                    'contract_type' => 'permanent',
                    'start_date' => now()->subMonths(6 + $index)->toDateString(),
                    'bank' => ['RAJHI', 'ALBILAD', 'RIYAD'][$index % 3],
                    'basic_salary' => $basic,
                    'housing_allowance' => $housing,
                    'transportation_allowance' => $transport,
                    'other_allowances' => $other,
                    'total' => $total,
                    'basic_salary_gosi' => $isSaudi ? $basic : 0,
                    'housing_allowance_gosi' => $isSaudi ? $housing : 0,
                    'social_insurance_saudi' => $gosi,
                    'total_deductions' => $gosi,
                    'remaining_salary' => $remaining,
                    'cash' => $remaining,
                ]);
            }
        });
    }
}
