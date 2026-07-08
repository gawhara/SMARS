<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        // Priority countries (CODEX §13) — shown first, in this exact order.
        $priority = [
            ['iso2' => 'EG', 'name_en' => 'Egypt', 'name_ar' => 'مصر'],
            ['iso2' => 'SA', 'name_en' => 'Saudi Arabia', 'name_ar' => 'المملكة العربية السعودية'],
            ['iso2' => 'SY', 'name_en' => 'Syria', 'name_ar' => 'سوريا'],
            ['iso2' => 'LB', 'name_en' => 'Lebanon', 'name_ar' => 'لبنان'],
            ['iso2' => 'PK', 'name_en' => 'Pakistan', 'name_ar' => 'باكستان'],
            ['iso2' => 'BD', 'name_en' => 'Bangladesh', 'name_ar' => 'بنغلاديش'],
            ['iso2' => 'IN', 'name_en' => 'India', 'name_ar' => 'الهند'],
            ['iso2' => 'JO', 'name_en' => 'Jordan', 'name_ar' => 'الأردن'],
            ['iso2' => 'IQ', 'name_en' => 'Iraq', 'name_ar' => 'العراق'],
            ['iso2' => 'QA', 'name_en' => 'Qatar', 'name_ar' => 'قطر'],
            ['iso2' => 'KW', 'name_en' => 'Kuwait', 'name_ar' => 'الكويت'],
            ['iso2' => 'AF', 'name_en' => 'Afghanistan', 'name_ar' => 'أفغانستان'],
            ['iso2' => 'ID', 'name_en' => 'Indonesia', 'name_ar' => 'إندونيسيا'],
            ['iso2' => 'PH', 'name_en' => 'Philippines', 'name_ar' => 'الفلبين'],
            ['iso2' => 'SD', 'name_en' => 'Sudan', 'name_ar' => 'السودان'],
            ['iso2' => 'TN', 'name_en' => 'Tunisia', 'name_ar' => 'تونس'],
            ['iso2' => 'DZ', 'name_en' => 'Algeria', 'name_ar' => 'الجزائر'],
            ['iso2' => 'MA', 'name_en' => 'Morocco', 'name_ar' => 'المغرب'],
        ];

        $base = 100;
        foreach ($priority as $index => $country) {
            Country::updateOrCreate(
                ['iso2' => $country['iso2']],
                $country + ['priority' => $base - $index],
            );
        }

        // Remaining countries (priority 0, alphabetical at render time).
        $others = [
            ['iso2' => 'AE', 'name_en' => 'United Arab Emirates', 'name_ar' => 'الإمارات العربية المتحدة'],
            ['iso2' => 'BH', 'name_en' => 'Bahrain', 'name_ar' => 'البحرين'],
            ['iso2' => 'OM', 'name_en' => 'Oman', 'name_ar' => 'عمان'],
            ['iso2' => 'YE', 'name_en' => 'Yemen', 'name_ar' => 'اليمن'],
            ['iso2' => 'PS', 'name_en' => 'Palestine', 'name_ar' => 'فلسطين'],
            ['iso2' => 'LY', 'name_en' => 'Libya', 'name_ar' => 'ليبيا'],
            ['iso2' => 'MR', 'name_en' => 'Mauritania', 'name_ar' => 'موريتانيا'],
            ['iso2' => 'SO', 'name_en' => 'Somalia', 'name_ar' => 'الصومال'],
            ['iso2' => 'DJ', 'name_en' => 'Djibouti', 'name_ar' => 'جيبوتي'],
            ['iso2' => 'KM', 'name_en' => 'Comoros', 'name_ar' => 'جزر القمر'],
            ['iso2' => 'TR', 'name_en' => 'Turkey', 'name_ar' => 'تركيا'],
            ['iso2' => 'IR', 'name_en' => 'Iran', 'name_ar' => 'إيران'],
            ['iso2' => 'LK', 'name_en' => 'Sri Lanka', 'name_ar' => 'سريلانكا'],
            ['iso2' => 'NP', 'name_en' => 'Nepal', 'name_ar' => 'نيبال'],
            ['iso2' => 'MM', 'name_en' => 'Myanmar', 'name_ar' => 'ميانمار'],
            ['iso2' => 'MY', 'name_en' => 'Malaysia', 'name_ar' => 'ماليزيا'],
            ['iso2' => 'TH', 'name_en' => 'Thailand', 'name_ar' => 'تايلاند'],
            ['iso2' => 'VN', 'name_en' => 'Vietnam', 'name_ar' => 'فيتنام'],
            ['iso2' => 'CN', 'name_en' => 'China', 'name_ar' => 'الصين'],
            ['iso2' => 'KR', 'name_en' => 'South Korea', 'name_ar' => 'كوريا الجنوبية'],
            ['iso2' => 'JP', 'name_en' => 'Japan', 'name_ar' => 'اليابان'],
            ['iso2' => 'ET', 'name_en' => 'Ethiopia', 'name_ar' => 'إثيوبيا'],
            ['iso2' => 'KE', 'name_en' => 'Kenya', 'name_ar' => 'كينيا'],
            ['iso2' => 'NG', 'name_en' => 'Nigeria', 'name_ar' => 'نيجيريا'],
            ['iso2' => 'GH', 'name_en' => 'Ghana', 'name_ar' => 'غانا'],
            ['iso2' => 'UG', 'name_en' => 'Uganda', 'name_ar' => 'أوغندا'],
            ['iso2' => 'TZ', 'name_en' => 'Tanzania', 'name_ar' => 'تنزانيا'],
            ['iso2' => 'ZA', 'name_en' => 'South Africa', 'name_ar' => 'جنوب أفريقيا'],
            ['iso2' => 'GB', 'name_en' => 'United Kingdom', 'name_ar' => 'المملكة المتحدة'],
            ['iso2' => 'US', 'name_en' => 'United States', 'name_ar' => 'الولايات المتحدة'],
            ['iso2' => 'CA', 'name_en' => 'Canada', 'name_ar' => 'كندا'],
            ['iso2' => 'FR', 'name_en' => 'France', 'name_ar' => 'فرنسا'],
            ['iso2' => 'DE', 'name_en' => 'Germany', 'name_ar' => 'ألمانيا'],
            ['iso2' => 'IT', 'name_en' => 'Italy', 'name_ar' => 'إيطاليا'],
            ['iso2' => 'ES', 'name_en' => 'Spain', 'name_ar' => 'إسبانيا'],
            ['iso2' => 'NL', 'name_en' => 'Netherlands', 'name_ar' => 'هولندا'],
            ['iso2' => 'RU', 'name_en' => 'Russia', 'name_ar' => 'روسيا'],
            ['iso2' => 'UA', 'name_en' => 'Ukraine', 'name_ar' => 'أوكرانيا'],
            ['iso2' => 'BR', 'name_en' => 'Brazil', 'name_ar' => 'البرازيل'],
            ['iso2' => 'AU', 'name_en' => 'Australia', 'name_ar' => 'أستراليا'],
        ];

        foreach ($others as $country) {
            Country::updateOrCreate(['iso2' => $country['iso2']], $country + ['priority' => 0]);
        }
    }
}
