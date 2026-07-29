<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Api\app\Models\AiChatDetail;
use Modules\Api\app\Resources\Api\AiChat\AiChatDetailResource;
use Modules\Chat\app\Models\Chat;
use Modules\Chat\app\Models\ChatDetail;
use Modules\User\Entities\User;

class ChatMessageSentPusher implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $user)
    {
        //
    }
    public function broadcastWith(): array
    {
        $chat = Chat::getRoom($this->user->id);
        $message = $chat->chatDetails->sortByDesc('created_at')->first();

        $flatItems = collect([
            [
                'type' => $message->is_question ? 'question' : 'answer',
                'date' => null,
                'content' => $message->content,
                'seen' => true,
                'time' => $message->created_at->format('H:i'),
            ]
        ]);
        return [
            'message' => $flatItems,
        ];
    }

    public function broadcastAs()
    {
        return env('PUSHER_CHAT_MESSAGE');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('user.' . $this->user->id),
        ];
    }
}
