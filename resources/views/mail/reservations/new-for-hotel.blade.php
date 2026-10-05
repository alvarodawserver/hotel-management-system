<x-mail::message>
# {{ __('New booking at :hotel', ['hotel' => $reservation->hotel->name]) }}

{{ __(':guest has booked and paid a stay. These are the details:', ['guest' => $reservation->guest_name]) }}

@include('mail.reservations.partials.stay', ['showRoomName' => true])

<x-mail::table>
| {{ __('Guest') }} | |
|:--|:--|
| {{ __('Main guest') }} | {{ $reservation->guest_name }} |
| {{ __('Contact phone') }} | {{ $reservation->guest_phone }} |
| {{ __('Email') }} | {{ $reservation->user->email }} |
</x-mail::table>

@if ($reservation->special_requests)
<x-mail::panel>
**{{ __('Special requests') }}:** {{ $reservation->special_requests }}
</x-mail::panel>
@endif

@include('mail.reservations.partials.price', ['totalLabel' => __('Total paid')])

<x-mail::button :url="$url">
{{ __('View reservation') }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
