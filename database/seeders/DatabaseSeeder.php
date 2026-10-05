<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AmenitySeeder::class,
            CategorySeeder::class,
            RoomTypeSeeder::class,
        ]);

        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->owner()->create([
            'name' => 'Propietario',
            'email' => 'owner@example.com',
        ]);

        User::factory()->create([
            'name' => 'Cliente',
            'email' => 'customer@example.com',
        ]);
    }
}
