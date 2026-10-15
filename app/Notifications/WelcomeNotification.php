<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from("al@mail.black.co.ke", "Alphaxard from Black Music")
            ->subject('Welcome to Black Music!')
            ->greeting('Welcome ' . $notifiable->username . ' to Black Music')
            ->line("Thanks for signing up! You've just joined the best online music store in Kenya. As a user you'll be able to:")
            ->line('- Follow your favourite musicians.')
            ->line('- Buy their videos and audios.')
            ->line('- Sell your own videos and audios.')
            ->line('And much more! To get you started we picked some awesome songs for you.')
            ->action('Check out the charts', url('/videos/charts'))
            ->line("Thank you and we're excited to have you!");
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'url' => '/profile/@blackmusic/view',
            'from' => '@blackmusic',
            'message' => 'Welcome ' . $notifiable->username . ' to Black Music.',
        ];
    }
}
