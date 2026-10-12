<?php

namespace App\Notifications;

use App\Models\SongPayout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SongPayoutNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SongPayout $songPayout)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from('al@mail.black.co.ke', 'Alphaxard from Black Music')
            ->subject('Withdrawal successful')
            ->markdown('emails.song-payout', [
                'amount' => $this->sent(),
                'phone' => $notifiable->phone,
            ]);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'url' => '/settings',
            'from' => '@blackmusic',
            'message' => 'KES ' . $this->sent() . ' has been sent to your M-Pesa for your bought songs.',
        ];
    }

    private function sent(): int
    {
        return (int) $this->songPayout->amount - $this->songPayout->fee;
    }
}
