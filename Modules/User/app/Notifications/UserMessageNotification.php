<?php

namespace Modules\User\app\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;

class UserMessageNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $title, public string $excerpt, public string $message,public mixed $params = null , public ?string $link = null)
    {
    }

    public function toFcm($notifiable)
    {
        if (empty($notifiable->fcm_token)) {
            Log::channel('fcm')->info('User without FCM token', [
                'user_id' => $notifiable->id,
            ]);
            return FcmMessage::create()->data([]); // مقدار خالی اما معتبر برگردون
        }

        Log::info('Sending FCM Notification', [
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'message' => $this->message,
            'link' => $this->link,
        ]);
        return FcmMessage::create()
            ->data([
                'title' => $this->title,
                'excerpt' => $this->excerpt,
                'message' => $this->message,
                'link' => $this->link,
                // TODO:: 'route' =>
            ])
            ->custom([
                'android' => [
                    'notification' => [
                        'color' => '#0A0A0A',
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'message',
                    ],
                ],
            ])
            ->notification(\NotificationChannels\Fcm\Resources\Notification::create()
                ->title($this->title)
                ->body($this->excerpt)
            );
    }

    public function via($notifiable): array
    {
        return [FcmChannel::class];
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
        public function toArray($notifiable): array
        {
            return [
                'title' => $this->title,
                'excerpt' => $this->excerpt,
                'message' => $this->message,
            ];
        }
}
