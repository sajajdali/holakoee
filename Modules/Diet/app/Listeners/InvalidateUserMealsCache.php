<?php

namespace Modules\Diet\app\Listeners;

use Cache;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Diet\app\Events\DietRequestAdded;
use Modules\Diet\Entities\DietRequest;

class InvalidateUserMealsCache
{
    public function handle(DietRequestAdded $event)
    {
        $user = $event->dietRequest->user;

        // بررسی که کاربر وجود داشته باشد
        if ($user) {
            $cacheKey = 'user_meals_' . $user->id;
            $cacheTag = 'user_' . $user->id;

            // حذف کلید کش مخصوص
            Cache::tags($cacheTag)->forget($cacheKey);
        }
    }
}
