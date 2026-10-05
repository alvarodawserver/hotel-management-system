<?php

namespace App\Notifications;

use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

/**
 * An email about a reservation. Emails are queued once the surrounding
 * transaction commits, and rendered in the recipient's language (User
 * implements HasLocalePreference), so every helper here formats for the
 * current app locale.
 */
abstract class ReservationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reservation $reservation)
    {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * The data every reservation email template uses.
     *
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        $this->reservation->loadMissing(['hotel', 'room.roomType', 'user']);

        return [
            'reservation' => $this->reservation,
            'checkIn' => $this->day($this->reservation->check_in),
            'checkOut' => $this->day($this->reservation->check_out),
            'guests' => __(':adults adults, :children children', [
                'adults' => $this->reservation->adults,
                'children' => $this->reservation->children,
            ]),
            'nights' => array_map(fn (array $night): array => [
                'date' => $this->day($night['date']),
                'discount_percent' => $night['discount_percent'],
                'price' => $this->money($night['price']),
            ], $this->reservation->price_breakdown),
            'subtotal' => $this->money($this->reservation->subtotal),
            'discount' => $this->money($this->reservation->discount),
            'total' => $this->money($this->reservation->total_price),
        ];
    }

    /**
     * An amount in euro cents for the current locale, e.g. "136,44 €".
     */
    protected function money(int $cents): string
    {
        return (string) Number::currency($cents / 100, 'EUR', app()->getLocale());
    }

    /**
     * A date for the current locale, e.g. "jue., 15 oct. 2026".
     */
    protected function day(CarbonInterface|string $date): string
    {
        return CarbonImmutable::parse($date)->settings(['locale' => app()->getLocale()])->isoFormat('ddd, D MMM YYYY');
    }

    /**
     * The hotel's refund tiers in plain sentences, as on the website.
     *
     * @return list<string>
     */
    protected function cancellationPolicy(): array
    {
        $tiers = $this->reservation->hotel->cancellation_policy;

        $sentences = array_map(fn (array $tier): string => $tier['refund_percent'] === 100
            ? __('Free cancellation up to :days days before check-in.', ['days' => $tier['days_before']])
            : __(':percent % refund if you cancel up to :days days before check-in.', [
                'percent' => $tier['refund_percent'],
                'days' => $tier['days_before'],
            ]), $tiers);

        $last = end($tiers);

        if ($last !== false) {
            $sentences[] = __('Cancelling less than :days days before check-in is not refunded.', ['days' => $last['days_before']]);
        }

        return $sentences;
    }
}
