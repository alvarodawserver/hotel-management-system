<?php

namespace App\Actions\Rooms;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateRoomsInBulk
{
    public const MAX_ROOMS = 50;

    /**
     * Create consecutively numbered rooms ("101", "102", …) sharing the same
     * type, capacity and price. Nothing is created if any number is taken.
     *
     * @param  array{room_type_id: int, capacity: int, price_per_night: int, quantity: int, first_number: int}  $data
     * @return list<string> The names of the created rooms.
     *
     * @throws ValidationException
     */
    public function handle(Hotel $hotel, array $data): array
    {
        $names = array_map(
            fn (int $number): string => (string) $number,
            range($data['first_number'], $data['first_number'] + $data['quantity'] - 1),
        );

        $takenNames = $hotel->rooms()->whereIn('name', $names)->orderBy('name')->pluck('name')->all();

        if ($takenNames !== []) {
            throw ValidationException::withMessages([
                'first_number' => __('These room numbers already exist: :numbers.', [
                    'numbers' => implode(', ', $takenNames),
                ]),
            ]);
        }

        DB::transaction(function () use ($hotel, $data, $names): void {
            foreach ($names as $name) {
                $room = new Room([
                    'room_type_id' => $data['room_type_id'],
                    'name' => $name,
                    'capacity' => $data['capacity'],
                    'price_per_night' => $data['price_per_night'],
                    'is_active' => true,
                ]);

                $hotel->rooms()->save($room);
            }
        });

        return $names;
    }
}
