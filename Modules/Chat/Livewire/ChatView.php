<?php

namespace Modules\Chat\Livewire;

use App\Events\ChatMessageSentPusher;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Chat\app\Events\AdminAnswerChatEvent;
use Modules\Chat\app\Models\Chat;
use Modules\Chat\Enum\ChatDetailTypeEnum;
use Modules\Chat\Enum\ChatStatusEnum;

class ChatView extends Component
{
    #[Url]
    public int $chatId = 0;

    public $chatMessage = '';


    #[Computed]
    public function chatList(): ?\Illuminate\Support\Collection
    {
        // چتِ انتخاب‌شده را فقط یک‌بار واکشی کن
        $chat = Chat::find($this->chatId);

        if (! $chat) {
            return null;
        }

        // اگر چت به کسی تخصیص ندارد یا به همین کاربر لاگین‌شده تخصیص دارد → مجاز به ریست
        $assigneeId  = $chat->assigned_to;
        $currentUser = auth()->id();
        $canMarkSeen = ! $assigneeId || ((int) $assigneeId === (int) $currentUser);

        if ($canMarkSeen && $chat->new_message_by_user > 0) {
            // فقط در صورت نیاز، مقدار را صفر کن تا از نوشتن بی‌دلیل جلوگیری شود
            $chat->forceFill(['new_message_by_user' => 0])->save();
        }

        // پیام‌ها را از جدید به قدیم بگیر و بر اساس روز گروه‌بندی کن
        $messages = $chat->chatDetails()
            ->orderBy('id')
            ->get();

        return $messages->groupBy(fn ($item) => $item->created_at->format('Y-m-d'));
    }

    #[Computed]
    public function chat(){
        return Chat::firstWhere('id', $this->chatId);
    }

    public function sendMessage(){
        if(empty($this->chatMessage)){
            $this->dispatch('error', message:'متن پیام خود را وارد کنید.');
            return;
        }
        if($this->chat == null){
            $this->dispatch('error', message:'لطفا گفت و گوی مد نظر خود را انتخاب کنید');
            return;
        }
        $this->chat?->chatDetails()->create([
            'content' => $this->chatMessage,
            'type' => ChatDetailTypeEnum::MESSAGE,
            'user_id' => auth()->id(),
        ]);
        $this->chat?->increment('new_message_by_support');
        $this->chat?->update([
            'status' => ChatStatusEnum::ANSWERED,
            'new_message_by_user' => 0,
        ]);
        //clear input
        $this->chatMessage = '';
//        AdminAnswerChatEvent::dispatch($this->chat);
        event(new ChatMessageSentPusher($this->chat->user));

    }

    #[On('echo:chat.{chatId},.UserAnswer')]
    public function newMessage(): void
    {
        unset($this->chatList);
    }

    public function selectChatRoom(int $chatId): void
    {
        $this->chatId = $chatId;
        $this->dispatch('chatRoomSelected');
    }

    public function render()
    {
        $user = auth()->user();

        //get all chats order by type and latest message
//        $chats = Chat::orderBy('status', 'desc')
//            ->orderBy('new_message_by_user', 'desc')
//            ->whereHas('chatDetails')
//            ->get();
        $chats = Chat::query()
            // مرتب‌سازی بر اساس وضعیت: اول status = 10، بعد status = 30، بعد باقی موارد
            ->orderByRaw("CASE
                    WHEN status = 10 THEN 1
                    WHEN status = 30 THEN 2
                    ELSE 3
                  END")

            ->orderByDesc('new_message_by_user')

            ->orderByDesc('created_at')

            ->when($user->isNutritionists(), function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })

            ->whereHas('chatDetails')

            // دریافت نتایج
            ->get();
        return view('chat::livewire.chat-view', compact('chats'));
    }
}
