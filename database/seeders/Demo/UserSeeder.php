<?php

namespace Database\Seeders\Demo;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /** Customers besides the demo account. */
    public const CUSTOMERS = 200;

    /**
     * The demo accounts of the README, three more hotel owners and the
     * customers who book. Everyone is verified and uses the password
     * "password".
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Marta Jiménez Soto',
            'email' => 'admin@example.com',
            'locale' => 'es',
            'created_at' => now()->subDays(500),
        ]);

        $owners = [
            ['Carmen Ruiz Delgado', 'owner@example.com', 'es'],
            ['Antonio Marín Cortés', 'antonio.marin@example.com', 'es'],
            ['Sofía Navarro Gil', 'sofia.navarro@example.com', 'es'],
            ['James Whitaker', 'james.whitaker@example.com', 'en'],
        ];

        foreach ($owners as [$name, $email, $locale]) {
            User::factory()->owner()->create([
                'name' => $name,
                'email' => $email,
                'locale' => $locale,
                'created_at' => now()->subDays(fake()->numberBetween(480, 495)),
            ]);
        }

        User::factory()->create([
            'name' => 'Javier Moreno Vega',
            'email' => 'customer@example.com',
            'locale' => 'es',
            'created_at' => now()->subDays(470),
        ]);

        $this->createCustomers();
    }

    /**
     * Mostly Spanish customers and some British ones. A third signed up
     * before the first demo stay, the rest over the year, a few this month.
     * One was deactivated recently.
     */
    private function createCustomers(): void
    {
        $emails = [];

        for ($index = 0; $index < self::CUSTOMERS; $index++) {
            $isEnglish = fake()->boolean(20);
            $name = $isEnglish
                ? fake('en_GB')->firstName().' '.fake('en_GB')->lastName()
                : fake()->firstName().' '.fake()->lastName().' '.fake()->lastName();

            $email = Str::slug(Str::ascii($name), '.');
            $emails[$email] = ($emails[$email] ?? 0) + 1;

            $daysAgo = match (true) {
                $index < self::CUSTOMERS * 0.35 => fake()->numberBetween(440, 469),
                $index < self::CUSTOMERS * 0.95 => fake()->numberBetween(30, 430),
                default => fake()->numberBetween(1, 25),
            };

            $signedUpAt = now()->subDays($daysAgo)->setTime(fake()->numberBetween(8, 23), fake()->numberBetween(0, 59));

            User::factory()->create([
                'name' => $name,
                'email' => $email.($emails[$email] > 1 ? $emails[$email] : '').'@example.com',
                'locale' => $isEnglish ? 'en' : 'es',
                'created_at' => $signedUpAt,
                'email_verified_at' => $signedUpAt->copy()->addMinutes(5),
                'deactivated_at' => $index === 3 ? now()->subDays(20) : null,
            ]);
        }
    }
}
