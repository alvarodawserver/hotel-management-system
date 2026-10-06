<x-mail::message>
# {{ __('Your review has been removed') }}

{{ __('Hello :name, our team has removed the review you wrote on :date about your stay at :hotel, because it does not follow our guidelines: reviews must describe the stay, without insults or inappropriate content.', ['name' => $review->user->name, 'date' => $writtenOn, 'hotel' => $review->hotel->name]) }}

<x-mail::panel>
**{{ __('Reason') }}:** {{ $review->deletion_reason }}
</x-mail::panel>

<x-mail::panel>
**{{ __('Your review') }}** ({{ $review->rating }}/5)<br>
{!! nl2br(e($review->comment)) !!}
</x-mail::panel>

{{ __('As the review was removed, this stay can no longer be reviewed.') }}

<x-mail::button :url="$url">
{{ __('View my reservation') }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
