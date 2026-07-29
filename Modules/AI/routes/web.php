<?php

use Illuminate\Support\Facades\Route;
use Modules\AI\Http\Controllers\AIController;
use Modules\AI\Services\TokenService;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([], function () {
    Route::get('ai/add_token' , function (){
        app(TokenService::class)->credit(
            userId: 1,                // آیدی کاربر
            amount: 200000,          // تعداد توکن
            referenceType: 'manual_topup',     // دلیل شارژ
            meta: ['by' => auth()->id()]// هر متادیتایی که می‌خوای
        );
    });

});
