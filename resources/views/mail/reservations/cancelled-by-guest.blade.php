<x-mail::message>
# {{ __('A guest has cancelled their booking') }}

{{ __(':guest has cancelled reservation :code at :hotel. The room is free again for those dates.', ['guest' => $reservation->guest_name, 'code' => $reservation->code, 'hotel' => $reservation->hotel->name]) }}

@include('mail.reservations.partials.stay', ['showRoomName' => true])

{{ __('Refunded to the guest following your cancellation policy: :amount.', ['amount' => $refund]) }}

<x-mail::button :url="$url">
{{ __('View reservation') }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
