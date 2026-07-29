<?php

namespace Modules\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Notifications\ChatRoomMessageNotification;
use App\UserSession;
use Illuminate\Http\Request;
use Modules\Api\Trait\ApiHandlerTrait;
use Modules\Api\Transformers\Chat\ChatResource;
use Modules\Chat\app\Models\Chat;
use Modules\Chat\Enum\ChatDetailTypeEnum;
use Modules\Chat\Enum\ChatStatusEnum;

class UserChatController extends Controller
{
    use ApiHandlerTrait;

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {

        $chat = Chat::getRoom(user_id: $request->user()->id);
        return $this->ok(ChatResource::make($chat));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        $chat = Chat::getRoom($user->id);
        if ($chat->user_id !== $request->user()->id) {
            return $this->notFound();
        }

        if ($chat->ban) {
            return $this->requestException(data: [
                'status' => false,
                'message' => 'شما از ارسال پیام محروم هستید'
            ]);
        }

        $message = $request->input('message');
        if (is_null($message)) {
            return $this->requestException(data: [
                'status' => false,
                'message' => 'پیام نمیتواند خالی باشد'
            ]);
        }

        $chatDetail = $chat->chatDetails()->create([
            'user_id' => $user->id,
            'type'    => ChatDetailTypeEnum::MESSAGE,
            'content' => $message,
        ]);

        $chat->increment('new_message_by_user');
        $chat->update(['status' => ChatStatusEnum::USER_SEND_QUESTION]);


        $chat->unsetRelation('chatDetails');             // اطمینان از خالی شدن کش
        $chat = $chat->fresh();                          // یا ->fresh('chatDetails')

        return $this->created(ChatResource::make($chat)); // << مدل، نه id
    }

}
