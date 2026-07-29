<?php

namespace Modules\Diet\app\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Diet\Entities\DietRequest;

class DietDateSynchronizer
{
    /**
     * Sync diet start/end dates and regenerate date_of_day
     * based on the given start date (usually send time)
     */
    public static function sync(DietRequest $diet ): void
    {
        DB::transaction(function () use ($diet) {

            /** @var DietRequest $diet */
            $diet = $diet->fresh();

            // تبدیل به Carbon (اگر nullable بود امن)
            $start = Carbon::parse($diet->start_date)->startOfDay();
            $end   = Carbon::parse($diet->end_date)->endOfDay();

            // 1. آپدیت تاریخ‌های اصلی (اختیاری ولی امن)
            $diet->update([
                'start_date' => $start,
                'end_date'   => $end,
            ]);

            // 2. بازسازی date_of_day بر اساس start_date و day_number
            $details = $diet->dietRequestDetails()
                ->orderBy('day_number')
                ->orderBy('meal_id')
                ->get();

            foreach ($details as $detail) {
                if ($detail->day_number > 0) {
                    $detail->update([
                        // day 1 = start_date
                        // day 2 = start_date + 1
                        'date_of_day' => $start
                            ->copy()
                            ->addDays($detail->day_number - 1)
                            ->toDateString(),
                    ]);
                }
            }
        });
    }
}
