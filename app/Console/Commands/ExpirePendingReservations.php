<?php

namespace App\Console\Commands;

use App\Actions\Reservations\ExpireReservation;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reservations:expire')]
#[Description('Expire pending reservations whose payment window has run out')]
class ExpirePendingReservations extends Command
{
    /**
     * A fallback for lost "checkout.session.expired" webhooks: it also closes
     * the Stripe payment page so the stay cannot be paid late.
     */
    public function handle(ExpireReservation $expireReservation): int
    {
        $expired = 0;

        Reservation::query()
            ->where('status', ReservationStatus::Pending)
            ->where('expires_at', '<=', now())
            ->each(function (Reservation $reservation) use ($expireReservation, &$expired): void {
                $expireReservation->handle($reservation);
                $expired++;
            });

        $this->components->info("Expired {$expired} pending reservation(s).");

        return self::SUCCESS;
    }
}
