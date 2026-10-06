<?php

namespace App\Http\Controllers;

use App\Actions\Pricing\CalculateStayPrice;
use App\Actions\Reservations\CalculateRefund;
use App\Actions\Reservations\CancelReservation;
use App\Actions\Reservations\CreateReservation;
use App\Actions\Reservations\StartCheckout;
use App\Actions\Reservations\SyncCheckoutSession;
use App\Enums\ReservationStatus;
use App\Http\Requests\CreateReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Hotel;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * The customer's side of booking: the booking page, paying, "My
 * reservations" and cancelling.
 */
class ReservationController extends Controller
{
    public const TABS = ['upcoming', 'past', 'cancelled'];

    /**
     * The customer's reservations, split into upcoming, past and cancelled.
     */
    public function index(Request $request): Response
    {
        $tab = $request->validate([
            'tab' => ['nullable', Rule::in(self::TABS)],
        ])['tab'] ?? 'upcoming';

        $today = Reservation::today()->toDateString();

        $reservations = Reservation::query()
            ->whereBelongsTo($request->user())
            ->with(['hotel.images', 'room.roomType'])
            // Past stays show whether they still await the guest's review.
            ->when($tab === 'past', fn (Builder $query) => $query->with('reviewIncludingRemoved.user'))
            ->when($tab === 'upcoming', fn (Builder $query) => $query
                ->blocking()
                ->whereDate('check_out', '>=', $today)
                ->orderBy('check_in'))
            ->when($tab === 'past', fn (Builder $query) => $query
                ->where('status', ReservationStatus::Confirmed)
                ->whereDate('check_out', '<', $today)
                ->orderByDesc('check_in'))
            ->when($tab === 'cancelled', fn (Builder $query) => $query
                ->whereIn('status', [ReservationStatus::Cancelled, ReservationStatus::Expired])
                ->orderByDesc('updated_at'))
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Reservation $reservation): array => ReservationResource::make($reservation)->resolve($request));

        return Inertia::render('reservations/index', [
            'reservations' => $reservations,
            'tab' => $tab,
        ]);
    }

    /**
     * The booking page: the stay, its price night by night, the hotel's
     * cancellation policy and the guest's details form.
     */
    public function create(CreateReservationRequest $request, CalculateStayPrice $calculateStayPrice): Response|RedirectResponse
    {
        $hotel = $this->publishedHotel($request);
        $stay = $request->stay();
        $checkIn = CarbonImmutable::parse($stay['check_in']);
        $checkOut = CarbonImmutable::parse($stay['check_out']);

        $room = $hotel->rooms()
            ->bookableFor($stay['adults'] + $stay['children'], $checkIn, $checkOut)
            ->where('room_type_id', $stay['room_type_id'])
            ->where('capacity', $stay['capacity'])
            ->where('price_per_night', $stay['price_per_night'])
            ->with(['roomType', 'images'])
            ->first();

        if ($room === null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('There are no rooms of this kind left for those dates.')]);

            return to_route('hotels.show', [
                'hotel' => $hotel->slug,
                'check_in' => $stay['check_in'],
                'check_out' => $stay['check_out'],
                'adults' => $stay['adults'],
                'children' => $stay['children'],
            ]);
        }

        $hotel->load(['images', 'offers' => fn ($query) => $query->where('is_active', true)]);

        return Inertia::render('reservations/create', [
            'hotel' => [
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'province' => $hotel->province->label(),
                'municipality' => $hotel->municipality,
                'stars' => $hotel->stars,
                'cover_url' => $hotel->images->first()?->url,
                'cancellation_policy' => $hotel->cancellation_policy,
            ],
            'room' => [
                'room_type' => $room->roomType->translation(),
                'capacity' => $room->capacity,
                'description' => $room->description,
                'image_url' => $room->images->first()?->url,
            ],
            'stay' => [...$stay, 'hotel' => $hotel->slug],
            'price' => $calculateStayPrice->handle($room, $checkIn, $checkOut, $hotel->offers)->toArray(),
            'guestName' => $request->user()->name,
            'paymentWindowMinutes' => Reservation::PAYMENT_WINDOW_MINUTES,
        ]);
    }

    /**
     * Hold a room and send the customer to Stripe's payment page.
     */
    public function store(StoreReservationRequest $request, CreateReservation $createReservation, StartCheckout $startCheckout): SymfonyResponse
    {
        $reservation = $createReservation->handle($request->user(), $this->publishedHotel($request), $request->booking());

        return $this->redirectToPayment($reservation, $startCheckout);
    }

    /**
     * A reservation's details. While the payment is being confirmed the page
     * polls; besides waiting for the Stripe webhook, each poll after coming
     * back from Stripe asks Stripe directly whether the page was paid.
     */
    public function show(Request $request, Reservation $reservation, CalculateRefund $calculateRefund, SyncCheckoutSession $syncCheckoutSession): Response
    {
        Gate::authorize('view', $reservation);

        if ($request->query('checkout') === 'success') {
            $syncCheckoutSession->handle($reservation);
        }

        $reservation->load(['hotel.images', 'room.roomType', 'reviewIncludingRemoved.user']);

        return Inertia::render('reservations/show', [
            'reservation' => ReservationResource::make($reservation)->resolve(),
            'refundQuote' => $reservation->canBeCancelled()
                ? $calculateRefund->handle($reservation, $request->user())->toArray()
                : null,
        ]);
    }

    /**
     * Open a new payment page for a reservation still awaiting payment.
     */
    public function pay(Reservation $reservation, StartCheckout $startCheckout): SymfonyResponse
    {
        Gate::authorize('view', $reservation);

        return $this->redirectToPayment($reservation, $startCheckout);
    }

    /**
     * Cancel the reservation and refund what the hotel's policy allows.
     */
    public function cancel(Request $request, Reservation $reservation, CancelReservation $cancelReservation): RedirectResponse
    {
        Gate::authorize('cancel', $reservation);

        $quote = $cancelReservation->handle($reservation, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $quote->amount > 0
                ? __('Reservation cancelled. We are refunding :amount to your card.', ['amount' => Number::currency($quote->amount / 100, 'EUR', app()->getLocale())])
                : __('Reservation cancelled.'),
        ]);

        return to_route('reservations.show', $reservation);
    }

    /**
     * Only published hotels take bookings; the action checks again inside
     * its transaction.
     */
    private function publishedHotel(CreateReservationRequest $request): Hotel
    {
        return Hotel::query()->published()->where('slug', $request->string('hotel'))->firstOr(
            fn () => throw ValidationException::withMessages([
                'room' => __('This hotel is not accepting bookings at the moment.'),
            ]),
        );
    }

    /**
     * If Stripe cannot open the payment page the room stays held, and the
     * customer can retry from the reservation page.
     */
    private function redirectToPayment(Reservation $reservation, StartCheckout $startCheckout): SymfonyResponse
    {
        try {
            return Inertia::location($startCheckout->handle($reservation));
        } catch (ValidationException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => collect($exception->errors())->flatten()->first()]);

            return to_route('reservations.show', $reservation);
        }
    }
}
