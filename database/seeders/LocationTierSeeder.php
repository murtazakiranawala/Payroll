<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

/**
 * Backfills School.location_tier by matching the city named in the
 * Compensation Policy's Annexure "4. Location Categories" against each
 * school's name. Only exact city-name matches are set - schools in cities
 * the policy doesn't list are left unset for Finance/Super Admin to assign
 * manually under Admin > Schools.
 */
class LocationTierSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            'tier_1' => ['Mumbai', 'Bangalore', 'Pune'],
            'tier_2' => ['Kolkata', 'Chennai', 'Nagpur', 'Vasai', 'Nasik', 'Indore', 'Secunderabad', 'Rajkot', 'Raipur', 'Ahmedabad'],
            'tier_3' => ['Bhopal', 'Kota', 'Kotah', 'Banswara', 'Godhra'],
        ];

        $schools = School::whereNull('location_tier')->get();

        foreach ($schools as $school) {
            foreach ($cities as $tier => $names) {
                foreach ($names as $city) {
                    if (preg_match('/\b'.preg_quote($city, '/').'\b/i', $school->name)) {
                        $school->update(['location_tier' => $tier]);

                        continue 3;
                    }
                }
            }
        }
    }
}
