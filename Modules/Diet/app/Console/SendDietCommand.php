<?php

namespace Modules\Diet\app\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Diet\Entities\DietRequest;
use Modules\Diet\Enum\DietRequestStatusEnum;
use Modules\Setting\Enum\SettingKeyEnum;
use Modules\User\app\Notifications\SmsNotification;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SendDietCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'diet:send-auto';

    /**
     * The console command description.
     */
    protected $description = 'Automatic sending of diets at the appointed time.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $requests = DietRequest::whereNotNull('send_at')
            ->with('user')
            ->where('send_at', '<=', $now)
            ->where('status', '!=', DietRequestStatusEnum::ACTIVE)
            ->get();

        if ($requests->isEmpty()) {
            $this->info('not diet found');
            return CommandAlias::SUCCESS;
        }

        foreach ($requests as $diet) {
            $user = $diet->user;
            if (!$user) {
                $this->warn("diet id {$diet->id} skipped because its user does not exist");
                continue;
            }

            $diet->update([
                'status' => DietRequestStatusEnum::ACTIVE,
                'active' => true,
            ]);

            // send sms
            $template = setting(SettingKeyEnum::SMS_AFTER_SEND_DIET);
            if (isset($template)) {
                try {
                    $user->notify(new SmsNotification($template, [$user->first_name ?? '']));
                } catch (\Throwable $exception) {
                    report($exception);
                    $this->warn("diet id {$diet->id} activated, but its SMS could not be sent");
                }
            }
            $this->info("diet id  {$diet->id} is active");
        }

        return CommandAlias::SUCCESS;
    }

    /**
     * Get the console command arguments.
     */

}
