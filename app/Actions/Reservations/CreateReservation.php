<?php

namespace App\Actions\Reservations;

use App\Actions\Pricing\CalculateStayPrice;
use App\Enums\ReservationStatus;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateReservation
{
    public function __construct(private CalculateStayPrice $calculateStayPrice) {}

    /**
     * Book a free room of the chosen kind (type, capacity and price) and
     * hold it while the customer pays.
     *
     * The hotel row is locked for the whole transaction, so two customers
     * booking the same hotel at once are served one after the other and
     * the last free room can never be sold twice.
     *
     * @param  array{room_type_id: int, capacity: int, price_per_night: int, check_in: string, check_out: string, adults: int, children: int, guest_name: string, guest_phone: string, special_requests: string|null}  $data
     *
     * @throws ValidationException
     */
    public function handle(User $customer, Hotel $hotel, array $data): Reservation
    {
        $checkIn = CarbonImmutable::parse($data['check_in'])->startOfDay();
        $checkOut = CarbonImmutable::parse($data['check_out'])->startOfDay();
        $guests = $data['adults'] + $data['children'];

        return DB::transaction(function () use ($customer, $hotel, $data, $checkIn, $checkOut, $guests): Reservation {
            $hotel = Hotel::query()->whereKey($hotel->id)->lockForUpdate()->firstOrFail();

            if (! $hotel->isPublished()) {
                throw ValidationException::withMessages([
                    'room' => __('This hotel is not accepting bookings at the moment.'),
                ]);
            }

            $room = $hotel->rooms()
                ->bookableFor($guests, $checkIn, $checkOut)
                ->where('room_type_id', $data['room_type_id'])
                ->where('capacity', $data['capacity'])
                ->where('price_per_night', $data['price_per_night'])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($room === null) {
                throw ValidationException::withMessages([
                    'room' => __('There are no rooms of this kind left for those dates.'),
                ]);
            }

            $price = $this->calculateStayPrice->handle($room, $checkIn, $checkOut);

            $reservation = new Reservation([
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'adults' => $data['adults'],
                'children' => $data['children'],
                'guest_name' => $data['guest_name'],
                'guest_phone' => $data['guest_phone'],
                'special_requests' => $data['special_requests'],
            ]);

            $reservation->forceFill([
                'user_id' => $customer->id,
                'hotel_id' => $hotel->id,
                'room_id' => $room->id,
                'status' => ReservationStatus::Pending,
                'price_breakdown' => $price->nights,
                'subtotal' => $price->subtotal,
                'discount' => $price->discount,
                'total_price' => $price->total,
                'expires_at' => now()->addMinutes(Reservation::PAYMENT_WINDOW_MINUTES),
            ])->save();

            return $reservation;
        });
    }
}
