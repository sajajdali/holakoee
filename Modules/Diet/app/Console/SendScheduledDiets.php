<?php

namespace Modules\Diet\app\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Diet\app\Services\DietDateSynchronizer;
use Modules\Diet\Entities\DietRequest;
use Modules\Diet\Enum\DietRequestStatusEnum;
use Modules\Setting\Enum\SettingKeyEnum;
use Modules\User\app\Notifications\SmsNotification;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SendScheduledDiets extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'diet:send-scheduled';

    /**
     * The console command description.
     */
    protected $description = 'Automatically send diets that are due';


    public function handle(): int
    {
        $now = now();

        $query = DietRequest::query()
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->whereNull('send_at')
            ->orderBy('id');

        $count = 0;

        $query->chunkById(200, function ($batch) use (&$count, $now) {
            foreach ($batch as $row) {
                // گارد همزمانی تا دوبار ارسال نشود
                DB::transaction(function () use ($row, $now, &$count) {
                    $fresh = DietRequest::whereKey($row->id)->lockForUpdate()->first();

                    // get diet errors

                    if(array_key_exists('report_diet',$fresh->detail)) {
                        $dietErrors = app('dietService')->resultFilterAndDisplayErrors($fresh->detail['report_diet']);
                        if ($dietErrors['count'] > 0){
                            return;
                        }
                    }

                    if (isset($fresh->dietPlan->detail['need_expert_approval'])){
                        if (!isset($fresh->detail['expert_approved'])){
                            return;
                        }
                    }

                    if ($fresh->send_at !== null) {
                        return; // در فاصله‌ی بین خواندن و قفل، جای دیگری ارسال شده
                    }


                    $dietCount = $fresh->dietPlan->day_count;


                    $fresh->status = DietRequestStatusEnum::ACTIVE;

                    $fresh->start_date = Carbon::now()->toDateTimeString();
                    $fresh->end_date =  Carbon::now()->addDays($dietCount)->toDateTimeString();
                    $fresh->send_at = $now;
                    $fresh->save();

                    DietDateSynchronizer::sync($fresh);


                    // send sms
                    $template = setting(SettingKeyEnum::SMS_AFTER_SEND_DIET);
                    if ($template && isset($fresh->user)  ){
                        $sendParameter = [
                            $fresh?->user?->first_name ?? 'کاربر گرامی',
                        ];
                        $fresh->user->notify(new SmsNotification($template, $sendParameter));
                    }

                    $this->info("Queued DietRequest #{$fresh->id} for sending.");
                    $count++;
                });
            }
        });

        $this->info("Total queued: {$count}");
        return CommandAlias::SUCCESS;
    }
}
