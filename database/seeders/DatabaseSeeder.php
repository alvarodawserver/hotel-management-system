<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the catalogues and the demo data. Model events stay on: hotels
     * get their slug and reservations their code from them.
     */
    public function run(): void
    {
        $this->call([
            AmenitySeeder::class,
            CategorySeeder::class,
            RoomTypeSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
