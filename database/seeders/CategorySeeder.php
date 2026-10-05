<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the hotel categories.
     */
    public function run(): void
    {
        $categories = [
            ['es' => 'Histórico', 'en' => 'Historic'],
            ['es' => 'Boutique', 'en' => 'Boutique'],
            ['es' => 'Lujo', 'en' => 'Luxury'],
            ['es' => 'Económico', 'en' => 'Budget'],
            ['es' => 'Romántico', 'en' => 'Romantic'],
            ['es' => 'Negocios', 'en' => 'Business'],
            ['es' => 'Familiar', 'en' => 'Family-friendly'],
            ['es' => 'Playa', 'en' => 'Beach'],
        ];

        foreach ($categories as $name) {
            Category::create(['name' => $name]);
        }
    }
}
