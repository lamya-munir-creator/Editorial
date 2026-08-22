<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    public $message;
    public $type;
    public $url;

    // نستقبل الرسالة، ونوعها (أخضر، أحمر، إلخ)، والرابط الذي يوجه إليه الإشعار
    public function __construct($message, $type = 'info', $url = '/')
    {
        $this->message = $message;
        $this->type = $type;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return ['database']; // حفظ الإشعار في قاعدة البيانات
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => $this->message,
            'type' => $this->type,
            'url' => $this->url,
        ];
    }
}
