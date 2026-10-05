<?php

namespace Database\Seeders;

use App\Enums\AmenityIcon;
use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    /**
     * Seed the amenities owners can tick for their hotels.
     */
    public function run(): void
    {
        $amenities = [
            [['es' => 'Wi-Fi gratuito', 'en' => 'Free Wi-Fi'], AmenityIcon::Wifi],
            [['es' => 'Piscina', 'en' => 'Swimming pool'], AmenityIcon::Waves],
            [['es' => 'Parking privado', 'en' => 'Private parking'], AmenityIcon::CircleParking],
            [['es' => 'Desayuno incluido', 'en' => 'Breakfast included'], AmenityIcon::Coffee],
            [['es' => 'Restaurante', 'en' => 'Restaurant'], AmenityIcon::Utensils],
            [['es' => 'Bar', 'en' => 'Bar'], AmenityIcon::Martini],
            [['es' => 'Gimnasio', 'en' => 'Gym'], AmenityIcon::Dumbbell],
            [['es' => 'Spa', 'en' => 'Spa'], AmenityIcon::Sparkles],
            [['es' => 'Aire acondicionado', 'en' => 'Air conditioning'], AmenityIcon::Snowflake],
            [['es' => 'Se admiten mascotas', 'en' => 'Pets allowed'], AmenityIcon::PawPrint],
            [['es' => 'Accesible', 'en' => 'Wheelchair accessible'], AmenityIcon::Accessibility],
            [['es' => 'Primera línea de playa', 'en' => 'Beachfront'], AmenityIcon::Umbrella],
            [['es' => 'Terraza', 'en' => 'Terrace'], AmenityIcon::Sun],
            [['es' => 'Jardín', 'en' => 'Garden'], AmenityIcon::Trees],
            [['es' => 'Recepción 24 horas', 'en' => '24-hour front desk'], AmenityIcon::ConciergeBell],
            [['es' => 'Traslado al aeropuerto', 'en' => 'Airport shuttle'], AmenityIcon::Plane],
            [['es' => 'Alquiler de bicicletas', 'en' => 'Bike rental'], AmenityIcon::Bike],
            [['es' => 'Club infantil', 'en' => 'Kids club'], AmenityIcon::Baby],
            [['es' => 'Lavandería', 'en' => 'Laundry'], AmenityIcon::WashingMachine],
            [['es' => 'Hotel sin humo', 'en' => 'Non-smoking hotel'], AmenityIcon::CigaretteOff],
        ];

        foreach ($amenities as [$name, $icon]) {
            Amenity::create(['name' => $name, 'icon' => $icon->value]);
        }
    }
}
