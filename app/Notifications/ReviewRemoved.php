<?php

namespace App\Notifications;

use App\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the guest: an admin removed their review, and why. Queued after the
 * moderation commits and rendered in the guest's language.
 */
class ReviewRemoved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Review $review)
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
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->review->loadMissing(['hotel', 'user', 'reservation']);
        $locale = app()->getLocale();

        return (new MailMessage)
            ->subject(__('Your review of :hotel has been removed', ['hotel' => $this->review->hotel->name]))
            ->markdown('mail.reviews.removed', [
                'review' => $this->review,
                'writtenOn' => CarbonImmutable::parse($this->review->created_at)->settings(['locale' => $locale])->isoFormat('D MMM YYYY'),
                'url' => route('reservations.show', $this->review->reservation),
            ]);
    }
}
