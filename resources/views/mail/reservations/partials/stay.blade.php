<x-mail::table>
| {{ __('Your stay') }} | |
|:--|:--|
| {{ __('Booking code') }} | **{{ $reservation->code }}** |
| {{ __('Hotel') }} | {{ $reservation->hotel->name }} |
| {{ __('Address') }} | {{ $reservation->hotel->address }}, {{ $reservation->hotel->municipality }} ({{ $reservation->hotel->province->label() }}) |
| {{ __('Room') }} | {{ $reservation->room->roomType->translation() }}@if ($showRoomName ?? false) · {{ __('No. :name', ['name' => $reservation->room->name]) }}@endif |
| {{ __('Check-in') }} | {{ $checkIn }} |
| {{ __('Check-out') }} | {{ $checkOut }} |
| {{ __('Guests') }} | {{ $guests }} |
</x-mail::table>
