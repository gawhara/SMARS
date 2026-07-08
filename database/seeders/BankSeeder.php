<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        // iban_code = the 2 digits embedded at positions 5-6 of a Saudi IBAN.
        $banks = [
            ['code' => 'RAJHI', 'iban_code' => '80', 'name_en' => 'Al Rajhi Bank', 'name_ar' => 'مصرف الراجحي'],
            ['code' => 'SNB', 'iban_code' => '10', 'name_en' => 'Saudi National Bank', 'name_ar' => 'البنك الأهلي السعودي'],
            ['code' => 'RIYAD', 'iban_code' => '20', 'name_en' => 'Riyad Bank', 'name_ar' => 'بنك الرياض'],
            ['code' => 'ALBILAD', 'iban_code' => '15', 'name_en' => 'Bank Albilad', 'name_ar' => 'بنك البلاد'],
            ['code' => 'ALINMA', 'iban_code' => '05', 'name_en' => 'Alinma Bank', 'name_ar' => 'مصرف الإنماء'],
            ['code' => 'ANB', 'iban_code' => '30', 'name_en' => 'Arab National Bank', 'name_ar' => 'البنك العربي الوطني'],
            ['code' => 'SABB', 'iban_code' => '45', 'name_en' => 'Saudi Awwal Bank (SABB)', 'name_ar' => 'البنك السعودي الأول'],
            ['code' => 'BSF', 'iban_code' => '55', 'name_en' => 'Banque Saudi Fransi', 'name_ar' => 'البنك السعودي الفرنسي'],
            ['code' => 'ALJAZIRA', 'iban_code' => '60', 'name_en' => 'Bank Aljazira', 'name_ar' => 'بنك الجزيرة'],
            ['code' => 'SAIB', 'iban_code' => '65', 'name_en' => 'The Saudi Investment Bank', 'name_ar' => 'البنك السعودي للاستثمار'],
        ];

        foreach ($banks as $bank) {
            Bank::updateOrCreate(['code' => $bank['code']], $bank + ['is_active' => true]);
        }
    }
}
