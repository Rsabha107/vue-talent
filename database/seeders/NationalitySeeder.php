<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NationalitySeeder extends Seeder
{
    public function run(): void
    {
        $nationalities = [
            ['num_code' => 4, 'alpha_2_code' => 'AF', 'alpha_3_code' => 'AFG', 'en_short_name' => 'Afghanistan', 'nationality' => 'Afghan'],
            ['num_code' => 8, 'alpha_2_code' => 'AL', 'alpha_3_code' => 'ALB', 'en_short_name' => 'Albania', 'nationality' => 'Albanian'],
            ['num_code' => 12, 'alpha_2_code' => 'DZ', 'alpha_3_code' => 'DZA', 'en_short_name' => 'Algeria', 'nationality' => 'Algerian'],
            ['num_code' => 20, 'alpha_2_code' => 'AD', 'alpha_3_code' => 'AND', 'en_short_name' => 'Andorra', 'nationality' => 'Andorran'],
            ['num_code' => 32, 'alpha_2_code' => 'AR', 'alpha_3_code' => 'ARG', 'en_short_name' => 'Argentina', 'nationality' => 'Argentine'],
            ['num_code' => 36, 'alpha_2_code' => 'AU', 'alpha_3_code' => 'AUS', 'en_short_name' => 'Australia', 'nationality' => 'Australian'],
            ['num_code' => 40, 'alpha_2_code' => 'AT', 'alpha_3_code' => 'AUT', 'en_short_name' => 'Austria', 'nationality' => 'Austrian'],
            ['num_code' => 48, 'alpha_2_code' => 'BH', 'alpha_3_code' => 'BHR', 'en_short_name' => 'Bahrain', 'nationality' => 'Bahraini'],
            ['num_code' => 50, 'alpha_2_code' => 'BD', 'alpha_3_code' => 'BGD', 'en_short_name' => 'Bangladesh', 'nationality' => 'Bangladeshi'],
            ['num_code' => 56, 'alpha_2_code' => 'BE', 'alpha_3_code' => 'BEL', 'en_short_name' => 'Belgium', 'nationality' => 'Belgian'],
            ['num_code' => 64, 'alpha_2_code' => 'BT', 'alpha_3_code' => 'BTN', 'en_short_name' => 'Bhutan', 'nationality' => 'Bhutanese'],
            ['num_code' => 76, 'alpha_2_code' => 'BR', 'alpha_3_code' => 'BRA', 'en_short_name' => 'Brazil', 'nationality' => 'Brazilian'],
            ['num_code' => 124, 'alpha_2_code' => 'CA', 'alpha_3_code' => 'CAN', 'en_short_name' => 'Canada', 'nationality' => 'Canadian'],
            ['num_code' => 156, 'alpha_2_code' => 'CN', 'alpha_3_code' => 'CHN', 'en_short_name' => 'China', 'nationality' => 'Chinese'],
            ['num_code' => 208, 'alpha_2_code' => 'DK', 'alpha_3_code' => 'DNK', 'en_short_name' => 'Denmark', 'nationality' => 'Danish'],
            ['num_code' => 818, 'alpha_2_code' => 'EG', 'alpha_3_code' => 'EGY', 'en_short_name' => 'Egypt', 'nationality' => 'Egyptian'],
            ['num_code' => 246, 'alpha_2_code' => 'FI', 'alpha_3_code' => 'FIN', 'en_short_name' => 'Finland', 'nationality' => 'Finnish'],
            ['num_code' => 250, 'alpha_2_code' => 'FR', 'alpha_3_code' => 'FRA', 'en_short_name' => 'France', 'nationality' => 'French'],
            ['num_code' => 276, 'alpha_2_code' => 'DE', 'alpha_3_code' => 'DEU', 'en_short_name' => 'Germany', 'nationality' => 'German'],
            ['num_code' => 300, 'alpha_2_code' => 'GR', 'alpha_3_code' => 'GRC', 'en_short_name' => 'Greece', 'nationality' => 'Greek'],
            ['num_code' => 344, 'alpha_2_code' => 'HK', 'alpha_3_code' => 'HKG', 'en_short_name' => 'Hong Kong', 'nationality' => 'Hong Konger'],
            ['num_code' => 356, 'alpha_2_code' => 'IN', 'alpha_3_code' => 'IND', 'en_short_name' => 'India', 'nationality' => 'Indian'],
            ['num_code' => 360, 'alpha_2_code' => 'ID', 'alpha_3_code' => 'IDN', 'en_short_name' => 'Indonesia', 'nationality' => 'Indonesian'],
            ['num_code' => 364, 'alpha_2_code' => 'IR', 'alpha_3_code' => 'IRN', 'en_short_name' => 'Iran', 'nationality' => 'Iranian'],
            ['num_code' => 368, 'alpha_2_code' => 'IQ', 'alpha_3_code' => 'IRQ', 'en_short_name' => 'Iraq', 'nationality' => 'Iraqi'],
            ['num_code' => 372, 'alpha_2_code' => 'IE', 'alpha_3_code' => 'IRL', 'en_short_name' => 'Ireland', 'nationality' => 'Irish'],
            ['num_code' => 376, 'alpha_2_code' => 'IL', 'alpha_3_code' => 'ISR', 'en_short_name' => 'Israel', 'nationality' => 'Israeli'],
            ['num_code' => 380, 'alpha_2_code' => 'IT', 'alpha_3_code' => 'ITA', 'en_short_name' => 'Italy', 'nationality' => 'Italian'],
            ['num_code' => 392, 'alpha_2_code' => 'JP', 'alpha_3_code' => 'JPN', 'en_short_name' => 'Japan', 'nationality' => 'Japanese'],
            ['num_code' => 400, 'alpha_2_code' => 'JO', 'alpha_3_code' => 'JOR', 'en_short_name' => 'Jordan', 'nationality' => 'Jordanian'],
            ['num_code' => 414, 'alpha_2_code' => 'KW', 'alpha_3_code' => 'KWT', 'en_short_name' => 'Kuwait', 'nationality' => 'Kuwaiti'],
            ['num_code' => 422, 'alpha_2_code' => 'LB', 'alpha_3_code' => 'LBN', 'en_short_name' => 'Lebanon', 'nationality' => 'Lebanese'],
            ['num_code' => 458, 'alpha_2_code' => 'MY', 'alpha_3_code' => 'MYS', 'en_short_name' => 'Malaysia', 'nationality' => 'Malaysian'],
            ['num_code' => 484, 'alpha_2_code' => 'MX', 'alpha_3_code' => 'MEX', 'en_short_name' => 'Mexico', 'nationality' => 'Mexican'],
            ['num_code' => 528, 'alpha_2_code' => 'NL', 'alpha_3_code' => 'NLD', 'en_short_name' => 'Netherlands', 'nationality' => 'Dutch'],
            ['num_code' => 554, 'alpha_2_code' => 'NZ', 'alpha_3_code' => 'NZL', 'en_short_name' => 'New Zealand', 'nationality' => 'New Zealander'],
            ['num_code' => 578, 'alpha_2_code' => 'NO', 'alpha_3_code' => 'NOR', 'en_short_name' => 'Norway', 'nationality' => 'Norwegian'],
            ['num_code' => 512, 'alpha_2_code' => 'OM', 'alpha_3_code' => 'OMN', 'en_short_name' => 'Oman', 'nationality' => 'Omani'],
            ['num_code' => 586, 'alpha_2_code' => 'PK', 'alpha_3_code' => 'PAK', 'en_short_name' => 'Pakistan', 'nationality' => 'Pakistani'],
            ['num_code' => 608, 'alpha_2_code' => 'PH', 'alpha_3_code' => 'PHL', 'en_short_name' => 'Philippines', 'nationality' => 'Filipino'],
            ['num_code' => 616, 'alpha_2_code' => 'PL', 'alpha_3_code' => 'POL', 'en_short_name' => 'Poland', 'nationality' => 'Polish'],
            ['num_code' => 620, 'alpha_2_code' => 'PT', 'alpha_3_code' => 'PRT', 'en_short_name' => 'Portugal', 'nationality' => 'Portuguese'],
            ['num_code' => 634, 'alpha_2_code' => 'QA', 'alpha_3_code' => 'QAT', 'en_short_name' => 'Qatar', 'nationality' => 'Qatari'],
            ['num_code' => 643, 'alpha_2_code' => 'RU', 'alpha_3_code' => 'RUS', 'en_short_name' => 'Russia', 'nationality' => 'Russian'],
            ['num_code' => 682, 'alpha_2_code' => 'SA', 'alpha_3_code' => 'SAU', 'en_short_name' => 'Saudi Arabia', 'nationality' => 'Saudi'],
            ['num_code' => 702, 'alpha_2_code' => 'SG', 'alpha_3_code' => 'SGP', 'en_short_name' => 'Singapore', 'nationality' => 'Singaporean'],
            ['num_code' => 710, 'alpha_2_code' => 'ZA', 'alpha_3_code' => 'ZAF', 'en_short_name' => 'South Africa', 'nationality' => 'South African'],
            ['num_code' => 410, 'alpha_2_code' => 'KR', 'alpha_3_code' => 'KOR', 'en_short_name' => 'South Korea', 'nationality' => 'South Korean'],
            ['num_code' => 724, 'alpha_2_code' => 'ES', 'alpha_3_code' => 'ESP', 'en_short_name' => 'Spain', 'nationality' => 'Spanish'],
            ['num_code' => 144, 'alpha_2_code' => 'LK', 'alpha_3_code' => 'LKA', 'en_short_name' => 'Sri Lanka', 'nationality' => 'Sri Lankan'],
            ['num_code' => 752, 'alpha_2_code' => 'SE', 'alpha_3_code' => 'SWE', 'en_short_name' => 'Sweden', 'nationality' => 'Swedish'],
            ['num_code' => 756, 'alpha_2_code' => 'CH', 'alpha_3_code' => 'CHE', 'en_short_name' => 'Switzerland', 'nationality' => 'Swiss'],
            ['num_code' => 764, 'alpha_2_code' => 'TH', 'alpha_3_code' => 'THA', 'en_short_name' => 'Thailand', 'nationality' => 'Thai'],
            ['num_code' => 792, 'alpha_2_code' => 'TR', 'alpha_3_code' => 'TUR', 'en_short_name' => 'Turkey', 'nationality' => 'Turkish'],
            ['num_code' => 784, 'alpha_2_code' => 'AE', 'alpha_3_code' => 'ARE', 'en_short_name' => 'United Arab Emirates', 'nationality' => 'Emirati'],
            ['num_code' => 826, 'alpha_2_code' => 'GB', 'alpha_3_code' => 'GBR', 'en_short_name' => 'United Kingdom', 'nationality' => 'British'],
            ['num_code' => 840, 'alpha_2_code' => 'US', 'alpha_3_code' => 'USA', 'en_short_name' => 'United States', 'nationality' => 'American'],
            ['num_code' => 704, 'alpha_2_code' => 'VN', 'alpha_3_code' => 'VNM', 'en_short_name' => 'Vietnam', 'nationality' => 'Vietnamese'],
        ];

        foreach ($nationalities as $nationality) {
            DB::table('nationalities')->updateOrInsert(
                ['alpha_2_code' => $nationality['alpha_2_code']],
                $nationality
            );
        }

        $this->command->info('Nationalities seeded successfully.');
    }
}
