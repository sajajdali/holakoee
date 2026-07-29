<?php

use App\Http\Controllers\TelegramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('shop.index');
//    return view('welcome');
});
Route::get('checkout/{id}', function ($id) {
    return view('shop.checkout', compact('id'));
});

Route::get('test_notification' , [\Modules\Api\Http\Controllers\Profile\DashboardController::class , 'testNotification']);
Route::get('test' , [\Modules\Api\Http\Controllers\Profile\DashboardController::class , 'testByService']);
Route::get('seeder' , [\Modules\Api\Http\Controllers\Profile\DashboardController::class , 'seeder']);
Route::get('seeder_pivot' , [\Modules\Api\Http\Controllers\Profile\DashboardController::class , 'seederPivot']);
Route::get('referral/{referral}' , \Modules\Admin\Livewire\ReferralUser::class)->name('referral.user');

Route::get('referral_test' , \Modules\Admin\Livewire\PaymentLivewire::class)->name('referral.test');

Route::post('/telegram/webhook', [TelegramController::class, 'handle']);
Route::post('/telegram/webhook_referral', [\App\Http\Controllers\TelegramControllerReferral::class, 'handle']);
Route::get('delete_data' , function (){
//   $c = \Modules\Diet\Entities\BasicFood::whereDate('created_at' , '>=' , \Carbon\Carbon::parse('2024-02-27'))->delete();
//   $c = \Modules\Recipe\app\Models\Recipe::whereDate('created_at' , '>=' , \Carbon\Carbon::parse('2024-06-02'))->delete();
//    $c = \Modules\Diet\Entities\Food::where('id' , '>' , '0')->delete();
    $c = \Modules\Reminder\app\Models\Reminder::where('id' , '>' , 0)->delete();
    dd($c);
});


Route::get('set_proxy' , [TelegramController::class , 'setProxy']);
Route::get('set_proxy_referral' , [TelegramController::class , 'setProxyReferral']);
Route::get('/telegram/set-commands', function () {
    $useProxy = filter_var(env('ACTIVE_PROXY'), FILTER_VALIDATE_BOOLEAN);

    $options = [];

    if ($useProxy) {
        $options['proxy'] = env('PROXY_URL');
    }

    $guzzle = new \GuzzleHttp\Client($options);
    $httpClient = new \Telegram\Bot\HttpClients\GuzzleHttpClient($guzzle);
    $telegram = new \Telegram\Bot\Api(env('TELEGRAM_BOT_AGENT_TOKEN'), false, $httpClient);

    $telegram->setMyCommands([
        ['command' => 'start', 'description' => 'شروع ربات'],
        ['command' => 'balance', 'description' => 'مشاهده موجودی 💰'],
        ['command' => 'help', 'description' => 'راهنما 📖'],
    ]);

    return response()->json(['status' => 'Commands set']);
});
Route::get('call_fire', function () {

    $result = Process::run("ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i /home/www/.ssh/id_ed25519 root@5.202.89.41 -p8787 'php /var/saman/ivr/amiri/voip/cal_fire.php 13:58 09122978167 98417792 77574'");
    if ($result->successful()) {
        echo "✅ Done: " . $result->output();
    } else {
        echo "❌ SSH error: " . $result->errorOutput();
    }
});

Route::get('refresh_test/{user_id}', function ($user_id) {

    $user = \Modules\User\Entities\User::find($user_id);
    event(new \App\Events\RefreshDashboardEvent($user));
});

Route::get('/telegram/set-commands', function () {
    $guzzle = new \GuzzleHttp\Client([
//            'proxy' => 'socks5h://192.168.1.1:9090',
        'proxy' => 'socks5h://127.0.0.1:30808',
    ]);
    $httpClient = new \Telegram\Bot\HttpClients\GuzzleHttpClient($guzzle);
    $telegram = new \Telegram\Bot\Api(env('TELEGRAM_BOT_AGENT_TOKEN'), false, $httpClient);
    $telegram->setMyCommands([
        ['command' => 'start', 'description' => 'شروع ربات'],
        ['command' => 'balance', 'description' => 'مشاهده موجودی 💰'],
        ['command' => 'help', 'description' => 'راهنما 📖'],
    ]);
    return response()->json(['status' => 'Commands set']);
});

Route::get('free-credit-extension', function (Request $request) {
    $days = $request->get('days', 20); // مقدار پیش‌فرض: ۲۰ روز

    if ($request->get('password') !== 'ss555848') {
        abort(403, 'Access denied');
    }

    $packageUsers = \Modules\Package\Entities\PackageUser::where('package_id', '!=', 4)
        ->where('end_at', '>', \Carbon\Carbon::now()->subDays(14))
        ->get();

    foreach ($packageUsers as $packageUser) {
        $packageUser->update([
            'end_at' => \Carbon\Carbon::parse($packageUser->end_at)->addDays($days)->toDateString(),
            'type' => \Modules\Package\Enum\PackageUserTypeEnum::IN_USE
        ]);
    }

    return "برای {$packageUsers->count()} کاربر، {$days} روز اضافه شد.";
})->name('free-credit-extension');
Route::get('/webhook-viewer', function () {
    return view('webhook-viewer');
});
