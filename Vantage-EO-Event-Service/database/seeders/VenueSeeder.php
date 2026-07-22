<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $venues = [
            [
                'name' => 'Jogja Expo Center',
                'address' => 'Jl. Raya Janti, Banguntapan, Yogyakarta',
                'capacity' => 5000,
            ],
            [
                'name' => 'Auditorium Universitas',
                'address' => 'Yogyakarta',
                'capacity' => 1000,
            ],
            [
                'name' => 'Convention Hall',
                'address' => 'Yogyakarta',
                'capacity' => 2500,
            ],
        ];

        foreach ($venues as $venue) {
            Venue::updateOrCreate(['name' => $venue['name']], $venue);
        }
    }
}
