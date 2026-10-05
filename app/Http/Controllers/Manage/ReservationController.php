<?php

namespace App\Http\Controllers\Manage;

use App\Actions\Reservations\CancelReservation;
use App\Actions\Reservations\RefundReservation;
use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\CancelReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Hotel;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reservations of the owner's hotels (every hotel for admins).
 */
class ReservationController extends Controller
{
    /**
     * List reservations with hotel, status, date and text filters.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'hotel' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(ReservationStatus::class)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'refund_failed' => ['nullable', 'boolean'],
        ]);

        $hotels = Hotel::query()
            ->withTrashed()
            ->when(! $user->isAdmin(), fn (Builder $query) => $query->where('owner_id', $user->id))
            ->whereHas('reservations')
            ->orderBy('name')
            ->get(['id', 'name']);

        $reservations = Reservation::query()
            ->with(['hotel.images', 'room.roomType', 'user'])
            ->when(! $user->isAdmin(), fn (Builder $query) => $query->whereHas(
                'hotel',
                fn (Builder $query) => $query->where('owner_id', $user->id),
            ))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('guest_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $query) => $query->where('email', 'like', "%{$search}%")));
            })
            ->when($filters['hotel'] ?? null, fn (Builder $query, int $hotelId) => $query->where('hotel_id', $hotelId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('check_out', '>', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('check_in', '<=', $to))
            ->when($filters['refund_failed'] ?? false, fn (Builder $query) => $query->where('refund_status', RefundStatus::Failed))
            ->orderByDesc('check_in')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Reservation $reservation): array => ReservationResource::make($reservation)->resolve($request));

        return Inertia::render('manage/reservations/index', [
            'reservations' => $reservations,
            'hotels' => $hotels->map(fn (Hotel $hotel): array => ['value' => (string) $hotel->id, 'label' => $hotel->name]),
            'statuses' => collect(ReservationStatus::cases())->map(fn (ReservationStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'hotel' => isset($filters['hotel']) ? (string) $filters['hotel'] : '',
                'status' => $filters['status'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
                'refund_failed' => (bool) ($filters['refund_failed'] ?? false),
            ],
        ]);
    }

    /**
     * A reservation with the guest's contact details and requests.
     */
    public function show(Request $request, Reservation $reservation): Response
    {
        Gate::authorize('view', $reservation);

        $reservation->load(['hotel.images', 'room.roomType', 'user', 'cancelledBy']);

        return Inertia::render('manage/reservations/show', [
            'reservation' => ReservationResource::make($reservation)->resolve(),
            'cancelledByName' => $reservation->cancelledBy?->name,
            'canRetryRefund' => $request->user()->can('retryRefund', $reservation)
                && $reservation->refund_status === RefundStatus::Failed,
        ]);
    }

    /**
     * The hotel cancels: the guest gets a full refund and the reason.
     */
    public function cancel(CancelReservationRequest $request, Reservation $reservation, CancelReservation $cancelReservation): RedirectResponse
    {
        $quote = $cancelReservation->handle($reservation, $request->user(), $request->string('reason')->trim()->toString());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $quote->amount > 0
                ? __('Reservation cancelled. The guest gets a full refund.')
                : __('Reservation cancelled.'),
        ]);

        return to_route('manage.reservations.show', $reservation);
    }

    /**
     * Try a failed refund again. The idempotency key is the same, so Stripe
     * never refunds twice.
     */
    public function retryRefund(Reservation $reservation, RefundReservation $refundReservation): RedirectResponse
    {
        Gate::authorize('retryRefund', $reservation);

        if ($reservation->refund_status !== RefundStatus::Failed) {
            throw ValidationException::withMessages([
                'refund' => __('This refund does not need to be retried.'),
            ]);
        }

        $refundReservation->handle($reservation);

        Inertia::flash('toast', $reservation->refund_status === RefundStatus::Failed
            ? ['type' => 'error', 'message' => __('The refund failed again. Check the payment service and try later.')]
            : ['type' => 'success', 'message' => __('Refund sent.')]);

        return back();
    }
}
