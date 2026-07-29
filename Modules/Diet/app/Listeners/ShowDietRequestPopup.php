<?php

namespace Modules\Diet\app\Listeners;

use Cache;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Api\Enum\PopupEnum;
use Modules\Diet\app\Events\DietRequestAdded;
use Modules\Diet\Entities\DietRequest;
use Modules\Diet\Enum\DietRequestStatusEnum;
use Modules\Diet\Popup\PopupClass;
use Modules\User\Enum\UserMetaEnum;

class ShowDietRequestPopup
{
    public function handle(DietRequestAdded $event)
    {
        $dietRequest = $event->dietRequest;
        $user = $dietRequest->user;

        if (!$user) {
            return;
        }

        if (
            $dietRequest->start_date &&
            $dietRequest->start_date->isPast() &&
            $dietRequest->end_date &&
            $dietRequest->active == 1 &&
            $dietRequest->end_date->isFuture() &&
            $dietRequest->status == DietRequestStatusEnum::ACTIVE)
        {
            $this->removePopup($user);
        }

        //When the diet start date is tomorrow
        elseif (
            $dietRequest->start_date &&
            $dietRequest->start_date->isFuture()
            && $dietRequest->status == DietRequestStatusEnum::ACTIVE)
        {
            $user->popup = PopupEnum::STARTING_TOMORROW->value;
        }

        //When the usage period has expired
        if (
            $dietRequest->getOriginal('status') == DietRequestStatusEnum::ACTIVE &&
            $dietRequest->status == DietRequestStatusEnum::END &&
            $dietRequest->end_date &&
            $dietRequest->end_date->isPast()
        ) {
            // Handle the condition when the status changes from 20 to 30
            $user->popup = PopupEnum::DIET_IS_OVER->value;
        }

        // show popup when rejected
        elseif (
             $dietRequest->status == DietRequestStatusEnum::REJECTED
        ) {
            $user->popup = PopupEnum::REJECTED_DIET->value;
        }


    }

    private function removePopup($user)
    {
        if ($user) {  // بررسی وجود user
            $meta = $user->metas()
                ->where('meta_key', UserMetaEnum::POPUP)
                ->first();
            if ($meta) {
                // If the record exists, delete it
                $user->metas()
                    ->where('meta_key', UserMetaEnum::POPUP)
                    ->delete();
            }
        }
    }
}
