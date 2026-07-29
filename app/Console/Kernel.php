<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Modules\Diet\app\Console\ChangeFoodRestriction;
use Modules\Diet\app\Console\CreateDietSample;
use Modules\Diet\app\Console\DisableExpiredDietRequests;
use Modules\Diet\app\Console\SendDietCommand;
use Modules\Diet\app\Console\SendFinishedDietSmsCommand;
use Modules\Diet\app\Console\SendScheduledDiets;
use Modules\Diet\Console\ConvertRecipeKarafsCommand;
use Modules\Diet\Console\CreateDietCommand;
use Modules\Recipe\app\Console\FixRecipeData;
use Modules\Recipe\app\Console\RecipeInsertDatabase;
use Modules\Reminder\app\Console\SendReminderscommand;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        CreateDietCommand::class,
        ConvertRecipeKarafsCommand::class,
        CreateDietSample::class,
        DisableExpiredDietRequests::class,
        RecipeInsertDatabase::class,
        FixRecipeData::class,
        ChangeFoodRestriction::class,
        SendReminderscommand::class,
        SendDietCommand::class,
        SendScheduledDiets::class,
        SendFinishedDietSmsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('diet:send-scheduled')->everyFifteenMinutes();
        $schedule->command('diet:disable-expired')->everyThreeHours();
        $schedule->command('app:sendreminders')->everyFifteenMinutes();
        $schedule->command('diet:send-finished-diet-sms')->everyThreeHours();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
