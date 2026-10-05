<x-mail::table>
| {{ __('Night') }} | {{ __('Price') }} |
|:--|--:|
@foreach ($nights as $night)
| {{ $night['date'] }}@if ($night['discount_percent'] > 0) (−{{ $night['discount_percent'] }} %)@endif | {{ $night['price'] }} |
@endforeach
@if ($reservation->discount > 0)
| {{ __('Subtotal') }} | {{ $subtotal }} |
| {{ __('Offer discount') }} | −{{ $discount }} |
@endif
| **{{ $totalLabel ?? __('Total') }}** | **{{ $total }}** |
</x-mail::table>
