<?php

namespace Modules\Chat\Livewire;

use App\Events\ChatMessageSentPusher;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Chat\app\Events\AdminAnswerChatEvent;
use Modules\Chat\app\Models\Chat;
use Modules\Chat\Enum\ChatDetailTypeEnum;
use Modules\Chat\Enum\ChatStatusEnum;
use Modules\User\Enum\UserMetaEnum;

class ChatView extends Component
{
    #[Url]
    public int $chatId = 0;

    #[Url(except: '')]
    public string $search = '';

    public string $searchInput = '';

    public $chatMessage = '';

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    #[Computed]
    public function chatList(): ?\Illuminate\Support\Collection
    {
        // چتِ انتخاب‌شده را فقط یک‌بار واکشی کن
        $chat = Chat::find($this->chatId);

        if (! $chat) {
            return null;
        }

        // اگر چت به کسی تخصیص ندارد یا به همین کاربر لاگین‌شده تخصیص دارد → مجاز به ریست
        $assigneeId = $chat->assigned_to;
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
    public function chat()
    {
        return Chat::firstWhere('id', $this->chatId);
    }

    public function sendMessage()
    {
        if (empty($this->chatMessage)) {
            $this->dispatch('error', message: 'متن پیام خود را وارد کنید.');

            return;
        }
        if ($this->chat == null) {
            $this->dispatch('error', message: 'لطفا گفت و گوی مد نظر خود را انتخاب کنید');

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
        // clear input
        $this->chatMessage = '';
        //        AdminAnswerChatEvent::dispatch($this->chat);
        try {
            event(new ChatMessageSentPusher($this->chat->user));
        } catch (\Throwable $exception) {
            Log::warning('Unable to broadcast chat message.', [
                'chat_id' => $this->chat->id,
                'user_id' => $this->chat->user_id,
                'exception' => $exception::class,
            ]);
        }

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

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->chatId = 0;
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->searchInput = '';
        $this->chatId = 0;
    }

    public function render()
    {
        $user = auth()->user();
        $search = trim($this->search);
        $normalizedSearch = str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $search
        );
        $mobileSearch = preg_replace('/[^0-9]+/', '', $normalizedSearch) ?? '';
        $nameParts = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // get all chats order by type and latest message
        //        $chats = Chat::orderBy('status', 'desc')
        //            ->orderBy('new_message_by_user', 'desc')
        //            ->whereHas('chatDetails')
        //            ->get();
        $chats = Chat::query()
            // مرتب‌سازی بر اساس وضعیت: اول status = 10، بعد status = 30، بعد باقی موارد
            ->orderByRaw('CASE
                    WHEN status = 10 THEN 1
                    WHEN status = 30 THEN 2
                    ELSE 3
                  END')

            ->orderByDesc('new_message_by_user')

            ->orderByDesc('created_at')

            ->when($user->isNutritionists(), function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })

            ->when($search !== '', function ($query) use ($mobileSearch, $nameParts) {
                $query->whereHas('user', function ($userQuery) use ($mobileSearch, $nameParts) {
                    $userQuery->where(function ($searchQuery) use ($mobileSearch, $nameParts) {
                        if ($mobileSearch !== '') {
                            $searchQuery->where('mobile', 'like', '%'.$mobileSearch.'%');
                        }

                        if ($nameParts !== []) {
                            $method = $mobileSearch !== '' ? 'orWhere' : 'where';

                            $searchQuery->{$method}(function ($nameQuery) use ($nameParts) {
                                foreach ($nameParts as $namePart) {
                                    $nameQuery->whereHas('metas', function ($metaQuery) use ($namePart) {
                                        $metaQuery
                                            ->whereIn('meta_key', [
                                                UserMetaEnum::FIRST_NAME->value,
                                                UserMetaEnum::LAST_NAME->value,
                                            ])
                                            ->where('meta_value', 'like', '%'.$namePart.'%');
                                    });
                                }
                            });
                        }
                    });
                });
            })

            ->whereHas('chatDetails')

            // دریافت نتایج
            ->get();

        return view('chat::livewire.chat-view', compact('chats'));
    }
}
