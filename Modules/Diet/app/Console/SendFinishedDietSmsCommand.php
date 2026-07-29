<?php

namespace Modules\Diet\app\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Diet\Entities\DietRequest;
use Modules\Setting\Enum\SettingKeyEnum;
use Modules\User\app\Notifications\SmsNotification;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SendFinishedDietSmsCommand extends Command
{
    protected $signature = 'diet:send-finished-diet-sms';

    protected $description = 'Send SMS to users whose diet has finished (only if they have no active diet).';

    private function handleSecond()
    {
        $now = Carbon::now()->subDay();
        $requests2 = DietRequest::query()
            ->where('active', 1)
            ->whereDate('end_date', '<', $now->toDateString())
            ->whereNull('detail->sms->get_new_diet_sms')
            //            ->whereNull('detail->sms->diet_finished_sent_at')
//            ->whereNull('detail->sms->diet_when_send_request_new_diet')
//            ->whereNull('detail->sms->diet_finished_skipped_has_active')
            ->with('user')
            ->get();
        $i = 0;
        foreach ($requests2 as $request) {
            $user = $request->user;

            if (!$user) {
                continue;
            }

            $hasActiveDiet = DietRequest::query()
                ->where('user_id', $user->id)
                ->where('active', 1)
                ->where('id', '>', $request->id)
                ->exists();



            if ($hasActiveDiet) {
                continue;
            }
            if (!$user->activePackage()){
                continue;
            }
            $this->line(
                "DietRequest ID: {$request->id} | User ID: {$user->id} | Mobile: {$user->mobile}"
            );
            $detail = $request->detail ?? [];
            $smsDetail = data_get($detail, 'sms', []);

            if (!empty($smsDetail['get_new_diet_sms'])) {
                continue;
            }
            $template = 'get_new_diet';
            $sendParameter = [$user->first_name];
            $user->notify(new SmsNotification($template, $sendParameter));
            $detail['sms']['get_new_diet_sms'] = $now->toDateTimeString();
            $request->forceFill(['detail' => $detail])->save();
            $i ++;
        }

        dd($i);
    }
    public function handle()
    {
        $now = Carbon::now();

        return $this->handleSecond();
        if (!$now->between(
            Carbon::createFromTime(8, 0),
            Carbon::createFromTime(15, 0)
        )) {
            $this->info('Command can only run between 08:00 and 11:00.');
            return CommandAlias::SUCCESS;
        }
        $today = Carbon::now()->subDay()->toDateString();
        $requests = DietRequest::query()
            ->where('active', 1)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '=', $today)
            ->whereNull('detail->sms->diet_finished_sent_at')
//            ->whereNull('detail->sms->diet_when_send_request_new_diet')
//            ->whereNull('detail->sms->diet_finished_skipped_has_active')
            ->with('user')
            ->get();



//        foreach ($requests as $request) {
//            $user = $request->user;
//
//            if (!$user) {
//                continue;
//            }
//
//            $this->line(
//                "DietRequest ID: {$request->id} | User ID: {$user->id} | Mobile: {$user->mobile}"
//            );
//        }

        if ($requests->isEmpty()) {
            $this->info('No finished diets found.');
            return CommandAlias::SUCCESS;
        }

        foreach ($requests as $request) {
            $user = $request->user;

            if (!$user) {
                continue;
            }


//            $hasActiveDiet = DietRequest::query()
//                ->where('user_id', $user->id)
//                ->where('active', 1)
//                ->whereNull('deleted_at')
//                ->where('id', '!=', $request->id)
//                ->where(function ($q) use ($now) {
//                    $q->where('end_date', '>', $now); // هنوز تمام نشده
//                })
//                ->exists();
//
//            $detail = $request->detail ?? [];
//            $detail['sms'] = $detail['sms'] ?? [];
//            if ($hasActiveDiet) {
//                $detail['sms']['diet_finished_skipped_has_active'] = $now->toDateTimeString();
//                $request->forceFill(['detail' => $detail])->save();
//                continue;
//            }

            // has new request
            $hasNewRequest = DietRequest::query()
                ->where('user_id', $user->id)
                ->where('active', 1)
                ->whereNull('deleted_at')
                ->where('id', '>', $request->id)
                ->exists();

            $detail = $request->detail ?? [];
            $detail['sms'] = $detail['sms'] ?? [];
            if ($hasNewRequest) {
                $detail['sms']['diet_when_send_request_new_diet'] = $now->toDateTimeString();
                $request->forceFill(['detail' => $detail])->save();
                continue;
            }

            $template = setting(SettingKeyEnum::SMS_AFTER_DIET_END_WEIGHT);

            if (!$template) {
                continue;
            }


            try {
                $sendParameter = [$user->first_name];
                $user->notify(new SmsNotification($template, $sendParameter));
                $detail['sms']['diet_finished_sent_at'] = $now->toDateTimeString();
                $request->forceFill(['detail' => $detail])->save();




            } catch (\Throwable $e) {
                report($e);

                $detail['sms']['diet_finished_failed_at'] = $now->toDateTimeString();
                $detail['sms']['diet_finished_failed_msg'] = mb_substr($e->getMessage(), 0, 190);
                $request->forceFill(['detail' => $detail])->save();
            }


        }

        $this->info("Processed {$requests->count()} finished diet requests.");
        return CommandAlias::SUCCESS;
    }
}
