<?php

namespace Modules\Chat\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Database\factories\ChatFactory;
use Modules\Chat\Enum\ChatStatusEnum;
use Modules\User\Entities\User;

class Chat extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */

    protected $guarded = ['id'];

    protected $casts = [
        'status' => ChatStatusEnum::class,
        'ban' => 'boolean',
    ];

    protected $with = ['user', 'chatDetails'];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * آخرین پشتیبانِ کاربر (بر اساس جدیدترین رکورد در pivot)
     */
    protected static function resolveNutritionistIdForUser(int $userId): ?int
    {
        // اگر فقط ID می‌خوایم، لازم نیست کل رابطه را لود کنیم:
        return DB::table('nutritionist_user')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')    // جدیدترین انتساب
            ->value('nutritionist_id');    // null اگر نداشت
    }

    /**
     * ساخت/دریافت چتِ کاربر؛
     * اگر nutritionist داشت → assigned_to را ست یا آپدیت می‌کند.
     */


    public static function getRoom($user_id): Chat
    {
        $assigneeId = \DB::table('nutritionist_user')
            ->where('user_id', $user_id)
            ->orderByDesc('id')
            ->value('nutritionist_id');   // int|null

        $chat = self::firstOrNew(['user_id' => $user_id]);

        if (! $chat->exists) {
            // ایجاد چت جدید (حتی اگر null باشد می‌نویسیم)
            $chat->status      = ChatStatusEnum::JUST_CREATED;
            $chat->assigned_to = $assigneeId; // int|null
            $chat->save();

            return $chat->fresh(['user','chatDetails','assignee']);
        }

        $chat->update([
            'new_message_by_support' => 0,
            'assigned_to'            => $assigneeId, // int|null
        ]);

        return $chat->fresh(['user','chatDetails','assignee']);
    }
    protected static function newFactory(): ChatFactory
    {
        //return ChatFactory::new();
    }

    public function chatDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChatDetail::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLatestMessageAgoAttribute(): ?string
    {
        return $this->chatDetails->last()?->created_at->format('Y-m-d H:i');
    }

    public function getLatestMessageExcerptAttribute(): ?string
    {
        //get excerpt of latest message
        return $this->chatDetails->last()?->content;
    }
}
