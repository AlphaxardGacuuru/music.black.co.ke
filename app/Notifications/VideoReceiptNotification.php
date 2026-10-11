<?php

namespace App\Notifications;

use App\Mail\VideoReceiptMail;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class VideoReceiptNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    /**
     * @param  array<int, Video>  $videos
     */
    public function __construct(public array $videos)
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
    public function toMail(object $notifiable): VideoReceiptMail
    {
        return (new VideoReceiptMail($this->videos))
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

        foreach ($this->videos as $video) {
            array_push($list, $video['name']);
        }

        // Check proper grammar
        if (count($this->videos) > 1) {
            $numberOfVideos = count($this->videos).' videos: ';
        } else {
            $numberOfVideos = count($this->videos).' video: ';
        }

        return [
            'url' => '/library',
            'from' => '@blackmusic',
            'message' => 'You bought '.$numberOfVideos.implode(', ', $list),
        ];
    }
}
