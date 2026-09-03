<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * The organization's actual 50-school roster. Codes are derived
     * deterministically from the name (spaces -> hyphens, uppercased),
     * which is safe here since every name in this list is already unique.
     */
    public const SCHOOLS = [
        'JEMS Dholka',
        'SHSS Khergone',
        'PHS Barwani',
        'QBS Burhanpur',
        'MSB Kolkata',
        'MSB Ahmedabad',
        'SGJES Kolkata',
        'JES Dohad',
        'MSB Godhra',
        'BEMS Lunavada',
        'MSB Indore',
        'BHSS Noorani Nagar',
        'MSB Chennai',
        'HHS Marol',
        'SHS Mumbai',
        'MEHS Dombivli',
        'NES Mumbra',
        'MSB Pune',
        'MSB Rajkot',
        'MHSS Mandasaur',
        'MTPS Surat',
        'SSSS Dungarpur',
        'SSSS Galiyakot',
        'TEMSSS Partapur',
        'SSSS Sagwara',
        'NTSSS Ratlam',
        'TEHSS Ujjain',
        'RQK Mumbai',
        'MSB Bhopal',
        'ABNS Indore',
        'TES Jamnagar',
        'MSB Secunderabad',
        'MSB Vasai',
        'TGHS Mumbai',
        'MSB Mumbai',
        'MSB Nagpur',
        'MSB Raipur',
        'MSB Nasik',
        'NES Rajkot',
        'MSB Kotah',
        'NHESSS Sunel',
        'SSSEMS Udaipur',
        'MM Chhoti Sadri',
        'MSB Bangalore',
        'MSB Banswara',
        'BES Dohad',
        'TSSS Salumbar',
        'MY Sidhpur',
        'SHS Suwasra',
        'JEMS Ahmedabad',
    ];

    public function run(): void
    {
        foreach (self::SCHOOLS as $name) {
            $code = self::codeFor($name);

            School::updateOrCreate(['code' => $code], [
                'name' => $name,
                'aiims_school_code' => "AIIMS-{$code}",
                'gl_cost_centre_code' => "CC-{$code}",
                'is_active' => true,
            ]);
        }
    }

    public static function codeFor(string $name): string
    {
        return strtoupper(str_replace(' ', '-', $name));
    }
}
