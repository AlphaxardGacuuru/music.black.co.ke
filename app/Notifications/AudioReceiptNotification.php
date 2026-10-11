<?php

namespace App\Notifications;

use App\Mail\AudioReceiptMail;
use App\Models\Audio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class AudioReceiptNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    /**
     * @param  array<int, Audio>  $audios
     */
    public function __construct(public array $audios)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): AudioReceiptMail
    {
        return (new AudioReceiptMail($this->audios))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $list = [];

        foreach ($this->audios as $audio) {
            array_push($list, $audio['name']);
        }

        // Check proper grammar
        if (count($this->audios) > 1) {
            $numberOfAudios = count($this->audios).' audios: ';
        } else {
            $numberOfAudios = count($this->audios).' audio: ';
        }

        return [
            'url' => '/library',
            'from' => '@blackmusic',
            'message' => 'You bought '.$numberOfAudios.implode(', ', $list),
        ];
    }
}
