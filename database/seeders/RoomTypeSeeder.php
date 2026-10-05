<?php

namespace Database\Seeders;

use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    /**
     * Seed the most common room types; admins add more on request.
     */
    public function run(): void
    {
        $roomTypes = [
            [['es' => 'Individual', 'en' => 'Single'], 1],
            [['es' => 'Doble', 'en' => 'Double'], 2],
            [['es' => 'Doble uso individual', 'en' => 'Double for single use'], 1],
            [['es' => 'Twin', 'en' => 'Twin'], 2],
            [['es' => 'Triple', 'en' => 'Triple'], 3],
            [['es' => 'Familiar', 'en' => 'Family'], 4],
            [['es' => 'Junior suite', 'en' => 'Junior suite'], 2],
            [['es' => 'Suite', 'en' => 'Suite'], 2],
        ];

        foreach ($roomTypes as [$name, $defaultCapacity]) {
            RoomType::create(['name' => $name, 'default_capacity' => $defaultCapacity]);
        }
    }
}
