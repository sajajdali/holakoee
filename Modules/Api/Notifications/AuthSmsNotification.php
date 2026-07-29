<?php

namespace Modules\Api\Notifications;

use App\Broadcasting\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Setting\Enum\SettingKeyEnum;

class AuthSmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public string $code , public string $device = 'android')
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms($notifiable)
    {
        return $notifiable->mobile;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(mixed $notifiable): array
    {
        if ($this->device === 'android') {
            $loginTemplate = setting(SettingKeyEnum::SMS_API_LOGIN_TEMPLATE);
        } else {
            $loginTemplate = setting(SettingKeyEnum::SMS_API_LOGIN_TEMPLATE_WEBAPP);
        }

        return [
            'template' => $loginTemplate,
            'receptor' => $notifiable->mobile,
            'params' => [
                $this->code,
            ],
        ];
    }
}
