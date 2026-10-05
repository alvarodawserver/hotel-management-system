<x-mail::message>
# {{ __('Your booking is confirmed!') }}

{{ __('Hello :name, your stay at :hotel is booked and paid. Keep your booking code: the hotel will ask for it at check-in.', ['name' => $reservation->user->name, 'hotel' => $reservation->hotel->name]) }}

@include('mail.reservations.partials.stay')

@include('mail.reservations.partials.price', ['totalLabel' => __('Total paid')])

## {{ __('Cancellation policy') }}

@foreach ($policy as $sentence)
- {{ $sentence }}
@endforeach

{{ __('You can cancel from your reservation up to the check-in day; any refund goes back to your card automatically.') }}

<x-mail::button :url="$url">
{{ __('View my reservation') }}
</x-mail::button>

{{ __('See you soon on the coast,') }}<br>
{{ config('app.name') }}
</x-mail::message>
