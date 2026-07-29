<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Setting\Enum\SettingKeyEnum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            $payStarSign = setting(SettingKeyEnum::PAYMENT_PAYSTAR_SIGN);
            $payStarToken = setting(SettingKeyEnum::PAYMENT_PAYSTAR_TOKEN);
        } catch (\Throwable $exception) {
            report($exception);
            return;
        }

        config([
            'payment.drivers.paystar.gatewayId' => $payStarToken,
            'payment.drivers.paystar.signKey' => $payStarSign,
        ]);

    }
}
