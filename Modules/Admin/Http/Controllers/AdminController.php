<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Log;
use Modules\Api\Http\Controllers\PaymentController;
use Modules\Setting\Enum\SettingKeyEnum;
use Modules\Transaction\Entities\Transaction;
use Modules\Transaction\Enum\TransactionStatusEnum;
use Modules\User\app\Notifications\SmsNotification;

class AdminController extends Controller
{
    public function logout()
    {
        \Illuminate\Support\Facades\Auth::guard('web')->logout();

        session()->flush(); // Clear session data

        return redirect(url('/'));
    }

    public function gumroadPing(Request $request)
    {
        $data = $request->all();

        Log::info('📦 Gumroad Ping Received', $data);

        // اعتبارسنجی Seller ID
        if ($request->get('seller_id') !== env('GUMROAD_SELLER_ID')) {
            return response()->json(['error' => 'Invalid seller ID'], 403);
        }
        // successfully transaction
        $custom = $request->input('custom_fields');
        $transactionId = $custom['transaction_id'] ?? null;
//        $userId = $custom['user_id'] ?? null;

        $transaction = Transaction::find($transactionId);
        if ($transaction->status == TransactionStatusEnum::PENDING || $transaction->status == TransactionStatusEnum::REJECTED) {
            $paymentController = new PaymentController();

            //send sms after order package
            $template = setting(SettingKeyEnum::SMS_AFTER_ORDER_PACKAGE);
            if ($template && isset( $transaction->user->first_name)) {
                $sendParameter = [
                    $transaction->user->first_name,
                ];
                $transaction->user->notify(new SmsNotification($template, $sendParameter));
            }

            $paymentController->assignToUser($transaction);
            if ($transaction->detail['user_wallet']) {
                $paymentController->handleWallet($transaction);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
