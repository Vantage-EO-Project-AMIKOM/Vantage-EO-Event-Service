<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Concert', 'description' => 'Live music and entertainment events.'],
            ['name' => 'Seminar', 'description' => 'Educational talks and professional seminars.'],
            ['name' => 'Workshop', 'description' => 'Interactive learning and practical workshops.'],
            ['name' => 'Festival', 'description' => 'Festivals and large community events.'],
            ['name' => 'Conference', 'description' => 'Business, technology, and industry conferences.'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }
    }
}
