<x-mail::message>
# {{ __('Your reservation has been cancelled') }}

@if ($cancelledByCustomer)
{{ __('Hello :name, as you asked, we have cancelled your reservation at :hotel.', ['name' => $reservation->user->name, 'hotel' => $reservation->hotel->name]) }}
@else
{{ __('Hello :name, we are sorry: your reservation at :hotel has been cancelled.', ['name' => $reservation->user->name, 'hotel' => $reservation->hotel->name]) }}

@if ($reservation->cancellation_reason)
<x-mail::panel>
**{{ __('Reason') }}:** {{ __($reservation->cancellation_reason) }}
</x-mail::panel>
@endif
@endif

@if (! $wasPaid)
{{ __('Nothing was charged for this reservation.') }}
@elseif ($refundAmount > 0)
<x-mail::panel>
{{ __('We are refunding :amount (:percent %) to the card you paid with. It can take 5 to 10 days to appear on your statement.', ['amount' => $refund, 'percent' => $refundPercent]) }}
</x-mail::panel>
@else
{{ __('This cancellation was not refunded, as it was made after the last refund tier of the hotel’s cancellation policy.') }}
@endif

@include('mail.reservations.partials.stay')

<x-mail::button :url="$url">
{{ __('View my reservation') }}
</x-mail::button>

{{ __('We hope to welcome you on the coast another time,') }}<br>
{{ config('app.name') }}
</x-mail::message>
