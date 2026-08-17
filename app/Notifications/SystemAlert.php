<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemAlert extends Notification
{
    use Queueable;

    protected $message;
    protected $type;
    protected $url;

    /**
     * Create a new notification instance.
     *
     * @param string $message The notification text
     * @param string $type The notification type (e.g. info, success, warning, error)
     * @param string|null $url Optional URL to redirect when clicked
     */
    public function __construct($message, $type = 'info', $url = null)
    {
        $this->message = $message;
        $this->type = $type;
        $this->url = $url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database']; // Only store in database for dashboard
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
            'message' => $this->message,
            'type' => $this->type,
            'url' => $this->url,
        ];
    }
}
