<?php

namespace App\Services;

use Carbon\Carbon;
use Modules\Setting\Enum\SettingKeyEnum;
use Modules\User\app\Notifications\SmsNotification;
use Modules\User\Entities\User;
use Modules\Package\Entities\Package;
use Modules\Package\Entities\PackageUser;
use Modules\Package\Enum\PackageUserTypeEnum;

class PackageAssigner
{
    /**
     */
    public static function assign(User $user, Package $package, ?Carbon $start = null, ?Carbon $end = null): PackageUser
    {
        // تاریخ‌های پیش‌فرض
        $start = $start ?? Carbon::now();
        $end   = $end   ?? Carbon::now()->addDays($package->days);

        // پکیج‌های فعال فعلی را لغو کن
        PackageUser::where('user_id', $user->id)
            ->where('type', PackageUserTypeEnum::IN_USE)
            ->get()
            ->each(function (PackageUser $row) {
                $row->update(['type' => PackageUserTypeEnum::CANCEL]);
            });

        // پکیج جدید
        $userPackage = PackageUser::create([
            'user_id'    => $user->id,
            'package_id' => $package->id,
            'start_at'   => $start,
            'end_at'     => $end,
            'type'       => PackageUserTypeEnum::IN_USE,
        ]);

        // Reminder ها
        if (method_exists($user, 'dispatchPaymentReminders')) {
            $user->dispatchPaymentReminders($package->days);
        }

        //send sms after order package
        $template = setting(SettingKeyEnum::SMS_AFTER_QUICK_SALE);
        if ($template) {
            $sendParameter = [
                $user->id,
            ];
            $user->notify(new SmsNotification($template, $sendParameter));
        }

        return $userPackage;
    }
}
