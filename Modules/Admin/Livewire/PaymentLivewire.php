<?php

namespace Modules\Admin\Livewire;

use App\UserSession;
use Livewire\Component;
use Livewire\Attributes\Title;
use Shetabit\Multipay\Invoice;
use Livewire\Attributes\Layout;
use Modules\User\Entities\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Shetabit\Payment\Facade\Payment;
use Modules\Package\Entities\Package;
use Modules\Setting\Enum\SettingKeyEnum;
use Modules\User\Services\WalletService;
use Modules\Package\Enum\PackageTypeEnum;
use Modules\User\app\Models\InviteFriend;
use Modules\Transaction\Entities\Transaction;
use App\Notifications\ChatRoomMessageNotification;
use Modules\Api\Http\Controllers\PaymentController;
use Modules\Transaction\Enum\TransactionStatusEnum;
use Modules\User\app\Notifications\SmsNotification;
use Modules\User\Http\Controllers\WalletController;
use App\Notifications\OrderTelegramMessageNotification;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

#[title('پرداخت')]
#[Layout('admin::layouts.login')]
class PaymentLivewire extends Component
{
    public $transaction = null;
    public $status = 'paying';
    public User $user;

    public $message = '';
    public $recaptcha;


    private function sendSmsToUser($user , $transactionCode): void
    {
        // send sms
        $template = setting(SettingKeyEnum::SMS_AFTER_USER_PAYMENT);
        if ($template ){
            $sendParameter = [
                $user->first_name,
                $transactionCode
            ];
            $user->notify(new SmsNotification($template, $sendParameter));
        }
    }
    private function verifyPayment()
    {
        $amount = $this->transaction->total_cost;
//        $amount = 1500;
        if (config('payment.default') == "paystar") {
            $payStarPin = setting(SettingKeyEnum::PAYMENT_PAYSTAR_TOKEN);
            $payStarSign = setting(SettingKeyEnum::PAYMENT_PAYSTAR_SIGN);
            $stringToHash = $amount . '#' . request()->ref_num . '#' . request()->card_number . '#' . request()->tracking_code;
            $hashedString = hash_hmac('sha512', $stringToHash, $payStarSign);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $payStarPin,
                'Content-Type' => 'application/json',
            ])->timeout(30)
                ->post('https://api.paystar.shop/api/pardakht/verify', [
                    'ref_num' => request()->ref_num,
                    'amount' => $amount,
                    'sign' => $hashedString,
                ]);
            if ($response->json()) {

                if ($response->json()['status'] == 1) {
                    $detail = $this->transaction->detail;
                    $detail['payStar']['payment_result'] = serialize($response->json());
                    $this->transaction->update([
                        'detail' => $detail,
                        'status' => TransactionStatusEnum::SUCCESSFUL,
                    ]);
                    $this->assignPackageToUser();
                    if ($amount > 0) {
                        $this->handleReferral();
                        $this->handleUserWallet();
                    }
                } else {
                    $this->status = 'showMessage';
                    $this->message = $response->json()['message'];
                }
            }
        } else {
            try {
                $receipt = Payment::amount($amount)
                    ->transactionId($this->transaction->detail['transaction_id_payment'])
                    ->verify();
                $tDetail = $this->transaction->detail;
                if (config('payment.default') == 'zibal') {
                    $newTdetail = array_merge($tDetail, [
                        'card_hash' => request()->get('trackId'),
                        'status' => request()->get('status'),
                        'ref_id' => request()->get('orderId'),
                        'orderId' => request()->get('orderId'),
                    ]);
                }
                $this->transaction->update([
                    'detail' => $newTdetail,
                    'status' => TransactionStatusEnum::SUCCESSFUL,
                ]);
                $this->assignPackageToUser();
                if ($amount > 0) {
                    $this->handleReferral();
                    $this->handleUserWallet();
                }

                // send sms to user
//                $this->sendSmsToUser($this->transaction->user, $this->transaction->transaction_code);
            } catch (InvalidPaymentException $exception) {
                Log::error('Payment ERROR: AppointmentUser ID => ' . $this->fetchData['app']->id, [
                    'error_message' => $exception->getMessage(),
                    'exception' => $exception
                ]);
                $this->status = 'showMessage';
                $this->message = 'خطا در انجام تراکنش';
            }
        }
    }

    public function handleReferralText()
    {
        //        $transaction = Transaction::latest()->first();
        $transaction = Transaction::find(579);
        $this->transaction = $transaction;
        $this->handleReferral();
    }

    private function handleReferral(): void
    {
        if ($this->transaction->status == TransactionStatusEnum::SUCCESSFUL) {
            $user = $this->transaction->user;

            $invitedUser = InviteFriend::where('user_invited_id', $user->id)->first();
            if ($invitedUser) {
                $referrerUser = $invitedUser->user;
                $percentage = $referrerUser->referralPercentage;

                // If the percentage has been directly specified by the management
                $amount = $this->transaction->total_cost;
                if (isset($percentage) && $percentage != '' && (int) $percentage > 0) {

                    $percentageReferrer = $amount * (int) $percentage / 100;
                } else {
                    $invitedAndPurchasedUsers = InviteFriend::where('user_id', $referrerUser->id)->where('benefit', '>', '0')->count();
                    if ($invitedAndPurchasedUsers < 6) {
                        $percentage = 5;
                    } elseif ($invitedAndPurchasedUsers < 11) {
                        $percentage = 10;
                    } else {
                        $percentage = 20;
                    }
                    $percentageReferrer = $amount * (int) $percentage / 100;
                }

                //when package with support
                $packageSelected = isset($this->transaction->detail['package_id']) ? $this->transaction->detail['package_id'] : null;
                if ($packageSelected) {
                    $package = Package::find($packageSelected);
                    if (!in_array($package->type, [PackageTypeEnum::DIET_AND_EXERCISE_WITH_SUPPORT, PackageTypeEnum::DIET_WITH_SUPPORT])) {
                        $percentageReferrer = max(0, (int) $percentageReferrer);
                    }
                }

                $newBenefit = $invitedUser->benefit + $percentageReferrer;
                $invitedUser->update([
                    'benefit' => $newBenefit,
                ]);
                $detail = ['change_wallet_charge_by' => $user->id, 'transaction_id' => $this->transaction->id];
                WalletService::addToWallet($referrerUser, $percentageReferrer, $detail);

                // send telegram notification
                $sessions = UserSession::where('user_id', $referrerUser->id)
                    ->where('type', '2')
                    ->where('state', 'waiting_for_agreement')
                    ->first();

                if ($sessions->count()) {
                    $referrerUser->notify(new OrderTelegramMessageNotification(user: $user, benefit: $newBenefit));
                }
            }
        }
    }

    public function handleUserWallet(): void
    {
        $transaction = $this->transaction;
        if (isset($transaction->detail['user_wallet']) && $transaction->detail['user_wallet'] > 0) {
            $userWallet = $this->transaction->user->wallet;
            $user = $transaction->user;
            $detail = ['change_wallet_charge_by' => $user->id, 'transaction_id' => $transaction->id];

            if ($userWallet >= $transaction->detail['user_wallet']) {
                WalletService::subtractFromWallet($user, $transaction->detail['user_wallet'], $detail);
            } else {
                WalletService::subtractFromWallet($user, $userWallet, $detail);
            }
        }
    }

    public function mount()
    {
        if (request()->routeIs('referral.test')) {
            $this->handleReferralText();
        }
        $transaction = request()->route('transaction');
        if ($transaction instanceof Transaction) {
            $this->transaction = $transaction;
            $this->user = $this->transaction->user;
            if (request()->tracking_code  && request()->status) {
                $this->verifyPayment();
            }
            if ($transaction->status == TransactionStatusEnum::PENDING && $transaction->total_cost == 0) {
                $paymentController = new PaymentController();
                $paymentController->assignToUser($this->transaction);
                $paymentController->handleWallet($this->transaction);
                $this->transaction->update([
                    'status' => TransactionStatusEnum::SUCCESSFUL
                ]);

                $this->status = 'showMessage';
                $this->message = 'پرداخت با استفاده از موجودی شما انجام شد';
            } elseif ($transaction->status == TransactionStatusEnum::SUCCESSFUL) {
                $this->status = 'showMessage';
                $this->message = 'پرداخت شما انجام شده و پکیج برای شما اختصاص یافته است';
            }
        }
    }

    private function gotoPayment()
    {
        // when sandbox
        if (env('PAYMENY_STATUS_SANDBOX') == 'true') {
            $this->transaction->update([
                'status' => TransactionStatusEnum::SUCCESSFUL,
            ]);
            $this->status = 'successful';
            $this->assignPackageToUser();
            return $this->render();
        }
        $amount = $this->transaction->total_cost;
//        $amount = 1500;
        $description = 'کاربر پرداخت کننده : ' . $this->user->full_name ?? 'بدون نام' . 'شماره تماس: ' . $this->user->mobile ?? 'بدون موبایل' . 'شماره تراکنش: ' . $this->transaction->id;
        $invoice = (new Invoice)->amount($amount)
            ->detail(['description' => $description, 'mobile' => $this->user?->mobile ?? null]);
        $callbackUrl = route('payment.transaction', ['tracking_code' => $this->transaction->transaction_code, 'transaction' => $this->transaction->id, 'call_back' => true]);
        $p = \Shetabit\Payment\Facade\Payment::config(['callbackUrl' => $callbackUrl])->purchase(
            $invoice,
            function ($driver, $transactionId) {
                $detail = $this->transaction->detail;
                $detail['driver'] = $driver;
                $detail['transaction_id_payment'] = $transactionId;
                $this->transaction->update(['detail' => $detail]);
            }
        )->pay();
        return $this->redirect($p->getAction());
    }

    public function onlinePayment()
    {
        if ($this->transaction->status = TransactionStatusEnum::PENDING) {
            $this->gotoPayment();
        }
    }

    private function assignPackageToUser()
    {
        $paymentController = new PaymentController();

        //send sms after order package
        $response = $paymentController->assignToUser($this->transaction);
        $template = setting(SettingKeyEnum::SMS_AFTER_ORDER_PACKAGE);
        if ($template && isset($this->user->first_name)) {
            $sendParameter = [
                $this->user->first_name,
            ];
            $this->user->notify(new SmsNotification($template, $sendParameter));
        }

        // assign agent
        if ($this->user->nutritionists()->count() == 0) {
            $agent = User::whereHas('roles', function ($q) {
                $q->where('id', 3);
            })
                ->withCount('clients')
                ->orderBy('clients_count', 'asc')
                ->inRandomOrder()
                ->first();

            if ($agent) {
                $this->user->nutritionists()->syncWithoutDetaching([$agent->id]);
            }
        }

        if ($this->transaction->detail['user_wallet']) {
            $paymentController->handleWallet($this->transaction);
        }
        if ($response->getStatusCode() == 200) {
            $this->status = 'successful';
        } else {
            $this->status = 'failed';
        }
    }

    public function successfulPayment()
    {
        if ($this->transaction) {
            if ($this->transaction->status == TransactionStatusEnum::PENDING || $this->transaction->status == TransactionStatusEnum::REJECTED) {
                $this->assignPackageToUser();
            } elseif ($this->transaction->status == TransactionStatusEnum::REJECTED) {
                $this->status = 'showMessage';
                $this->message = 'پرداخت شما با مشکل مواجه شده و لطفا مجدد از طریق اپلیکیشن اقدام کنید';
            } elseif ($this->transaction->status == TransactionStatusEnum::SUCCESSFUL) {
                $this->status = 'showMessage';
                $this->message = 'پرداخت شما انجام شده و پکیج برای شما اختصاص یافته است';
            }
        }
    }

    public function paymentAgain()
    {
        $this->status = 'paying';
    }

    public function paymentDollar()
    {
        $packageId = $this->transaction->detail['package_id'];
        $package = Package::find($packageId);
        $productUrl = $package->detail['api_url'];
        $userId = $this->user->id;
        $link = "{$productUrl}?user_id={$userId}&transaction_id={$this->transaction->id}";
        return redirect()->away($link);
        //        $this->status = 'paypal';
    }

    public function paymentFailed()
    {
        $this->transaction->update(['status' => TransactionStatusEnum::REJECTED]);

        $this->status = 'failed';
    }

    public function render()
    {
        return view('admin::livewire.payment');
    }
}
