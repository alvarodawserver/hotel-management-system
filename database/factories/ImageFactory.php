<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'imageable_type' => Hotel::class,
            'imageable_id' => Hotel::factory(),
            'path' => 'hotels/'.fake()->uuid().'.webp',
            'position' => 0,
        ];
    }
}
