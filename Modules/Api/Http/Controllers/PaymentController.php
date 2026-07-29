<?php

namespace Modules\Api\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Modules\Api\Enum\PopupEnum;
use Modules\Api\Http\Requests\DiscountRequest;
use Modules\Api\Http\Requests\StoreRequest;
use Modules\Api\Trait\ApiHandlerTrait;
use Modules\Api\Transformers\Package\PackageResource;
use Modules\Api\Transformers\UserResource;
use Modules\Coupon\Entities\Coupon;
use Modules\Diet\Entities\DietPlan;
use Modules\Diet\Entities\DietRequest;
use Modules\Exercise\Entities\ExercisePlanRequest;
use Modules\Exercise\Entities\ExercisePlanStrategy;
use Modules\Exercise\Enum\ExercisePlanRequestEnum;
use Modules\Exercise\Enum\ExercisePlanStrategyGenderEnum;
use Modules\Exercise\Enum\ExercisePlanStrategyLevelEnum;
use Modules\Exercise\Enum\ExercisePlanStrategyTargetEnum;
use Modules\Package\Entities\Package;
use Modules\Package\Enum\PackageTypeEnum;
use Modules\Package\Enum\PackageUserTypeEnum;
use Modules\Reminder\app\Models\Reminder;
use Modules\Reminder\app\Models\ReminderQueue;
use Modules\Reminder\Enum\ReminderParametersEnum;
use Modules\Reminder\Enum\ReminderTypeEnum;
use Modules\Setting\Enum\SettingKeyEnum;
use Modules\Transaction\Entities\Transaction;
use Modules\Transaction\Enum\TransactionPaidEnum;
use Modules\Transaction\Enum\TransactionStatusEnum;
use Modules\User\Enum\UserMetaEnum;
use Modules\User\Http\Controllers\WalletController;

class PaymentController extends Controller
{
    use ApiHandlerTrait;

    public function discount(DiscountRequest $request): \Illuminate\Http\JsonResponse
    {
        $discountCode = $request->input('discount_code');
        $packageId = $request->input('package_id');
        $coupon = Coupon::Active()->where('code', $discountCode)->first();
        $package = Package::Active()->whereId($packageId)->first();

        $handleDiscount = $this->handleDiscount($coupon, $package);
        if ($handleDiscount['status']) {
            return $this->ok($handleDiscount);
        }
        return $this->badRequest($handleDiscount);
    }

    private function handleDiscount($coupon, $package): array
    {
        if (!$package) {
            return ([
                'status' => false,
                'message' => 'هیچ بسته ای یافت نشد'
            ]);
        }
        if ($coupon) {
            // manage date
            $startAt = $coupon->start_at;
            $endAt = $coupon->end_at;
            if (isset($startAt) && verta($startAt)->isFuture()) {
                return (['status' => false, 'message' => 'زمان استفاده از کد فرا نرسیده است']);
            }
            if (isset($endAt) && verta($endAt)->isPast()) {
                return (['status' => false, 'message' => 'زمان استفاده از کد گذشته است']);
            }
            // manage date

            $canUsedFor = $coupon->can_used_for;
//            if ($canUsedFor->value !== $package->type->value) {
//                return (['status' => false, 'message' => 'این کوپن برای این بسته نیست']);
//            }

            // Minimum and maximum coupon amount
            $minimumSpend = $coupon->minimum_spend;
            $maximumSpend = $coupon->maximum_spend;

            if ($minimumSpend && $minimumSpend > $package->getPrice()) {
                return (['status' => false, 'message' => 'این کوپن برای این بسته قابل استفاده نیست']);
            }
            if ($maximumSpend && $maximumSpend < $package->getPrice()) {
                return (['status' => false, 'message' => 'این کوپن برای این بسته قابل استفاده نیست']);
            }
            // Minimum and maximum coupon amount

            // usable count
            if ($coupon->usable_count && $coupon->used >= $coupon->usable_count) {
                return (['status' => false, 'message' => 'محدودیت استفاده کوپن به اتمام رسیده است']);
            }
            // usable count

            $payablePrice = $package->getPrice();
            if ($coupon->is_percent && $coupon->value <= 100) {
                $originalAmount = $package->getPrice();
                $discount = $originalAmount * ($coupon->value / 100);
                $finalAmount = $originalAmount - $discount;
                $payablePrice = round($finalAmount, 2);
            } elseif (!$coupon->is_percent) {
                if ($coupon->value < $payablePrice) {
                    $payablePrice = $payablePrice - $coupon->value;
                } else {
                    $payablePrice = 0;
                }
            }

            $wallet = auth()->user()->wallet;
            $requiredAmountOfWallet = 0;
            if ($wallet > 0) {
                $requiredAmountOfWallet = ($wallet >= $payablePrice) ? $payablePrice : $wallet;
                $payablePrice -= $wallet;
            }

            return ([
                'status' => true,
                'message' => 'کد درسته',
                'coupon_id' => $coupon->id,
                'amount' => $payablePrice,
                'discount' => $discount,
                'required_amount_of_wallet' => $requiredAmountOfWallet,
                'package' => getPackage($package->id, $payablePrice)
            ]);
        } else {
            return (['status' => false, 'message' => 'کدی یافت نشد']);
        }
    }

    public function store(StoreRequest $request): \Illuminate\Http\JsonResponse
    {
        $discountCode = $request->input('discount_code');
        $couponId = $request->input('coupon_id');
        $packageId = $request->input('package_id');
//        $dietPlanId = $request->input('diet_plan_id');
//        $target_plan = $request->input('target_plan');
//        $weight = $request->input('weight') ?? null;
        $package = Package::Active()->whereId($packageId)->first();
        $user = $request->user();

        $price = $package->getPrice();
        $totalPrice = $price;
        $discountPrice = $package->getDiscountPackage();
        //        $payStarToken = setting(SettingKeyEnum::PAYMENT_PAYSTAR_TOKEN);
        $amountOfWallet = 0;
        if ($discountCode && $couponId) {
            $coupon = Coupon::Active()->whereId($couponId)->whereCode($discountCode)->first();
            $handleDiscount = $this->handleDiscount($coupon, $package);
            if (!$handleDiscount['status']) {
                return $this->badRequest($handleDiscount);
            }
            $totalPrice = $handleDiscount['amount'];
            $discountPrice += $handleDiscount['discount'];
            $amountOfWallet = $handleDiscount['required_amount_of_wallet'];
        } else{
            if ($user->wallet > 0){
                $amountOfWallet = ($user->wallet >= $totalPrice) ? $totalPrice : $user->wallet;
                $totalPrice -=  $amountOfWallet;
            }
        }

        $totalPrice = max($totalPrice, 0);


        $newTransaction = Transaction::create([
            'user_id' => $user->id,
            'coupon_id' => $couponId,
            'transaction_code' => Transaction::generateTransactionCode(),
            'status' => TransactionStatusEnum::PENDING,
            'cost' => $package->getOriginalPrice(),
            'payment_for' => $package->type,
            'total_cost' => $totalPrice,
            'discount' => $discountPrice,
            'ip' => request()->ip(),
            'paid_by' => TransactionPaidEnum::ONLINE,
            'detail' => [
                'package_id' => $package->id,
                'user_wallet' => $amountOfWallet,
            ]
        ]);

        //if payment in sanbox mood

        // when No need to pay the amount
        if ($totalPrice == 0) {
//            $this->assignToUser($newTransaction);
//            $this->handleWallet($newTransaction);
            return $this->ok([
                'status' => true,
                'message' => 'پرداخت با استفاده از موجودی کیف پول انجام خواهد شد',
                'payment_link' => str_replace("api", 'crm', route('payment.transaction', $newTransaction)),
            ]);
        }

        // when No need to pay the amount

        return $this->ok([
            'status' => true,
            'payment_link' => str_replace("api", 'crm', route('payment.transaction', $newTransaction)),
        ]);

        //if payment in sanbox mood

    }

    private function insertReminders($userModel , $package)
    {
        ReminderQueue::where('user_id', $userModel->id)
            ->where('reminder_for', ReminderTypeEnum::PACKAGE_PURCHASED)
            ->delete();
        $package_reminder = Reminder::where(function ($query) {
            $query->where('send_for', ReminderTypeEnum::PACKAGE_PURCHASED)
                ->orWhere('send_for', ReminderTypeEnum::PACKAGE_EXPIRED_NO_RENEWAL);
        })->get();

        if ($package_reminder->isNotEmpty()) {
            $package_reminder->each(function ($remidner) use ($package ,$userModel) {
                $detail = [];
                $params = $remidner->parameters;
                if (!empty($params)) {
                    foreach ($params as $param) {
                        $detail['params'][] = ReminderParametersEnum::tryFrom($param);
                    }
                }

                if ($remidner->send_for == ReminderTypeEnum::PACKAGE_EXPIRED_NO_RENEWAL) {
                    $send_at = \now()->addDays($package->days)->addDays($remidner->send_day)->addHours($remidner->send_time);
                } else {
                    $send_at = \now()->addDays($remidner->send_day)->addHours($remidner->send_time);
                }
                if ($remidner->send_day < 0  && $remidner->send_for == ReminderTypeEnum::PACKAGE_PURCHASED) {
                    $send_at = \now()->addDays($package->days)->subDays(abs($remidner->send_day));
                }
                $model = [
                    'user_id' =>      $userModel->id,
                    'reminder_type' => $remidner->status,
                    'reminder_for'  => $remidner->send_for,
                    'reminder_id'   => $remidner->id,
                    'send_at'       => $send_at,
                    'detail'        => $detail
                ];
                ReminderQueue::create($model);
            });
        }
    }

    public function assignToUser($transaction)
    {
        if ($transaction->coupon) {
            $transaction->coupon->increment('used');
        }
        $transaction->update(['status' => TransactionStatusEnum::SUCCESSFUL]);
        $package = Package::find($transaction->detail['package_id']) ?? '';
        $user = $transaction->user;
        $transaction->user->packages()->where('type', PackageUserTypeEnum::IN_USE)->update(['type' => PackageUserTypeEnum::CANCEL]);
        $transaction->user->packages()->create([
            'package_id' => $transaction->detail['package_id'] ?? '',
            'start_at' => Carbon::now()->toDateString(),
            'end_at' => $package ? Carbon::now()->addDays($package->days ?? 0)->toDateString() : null,
            'type' => PackageUserTypeEnum::IN_USE,
        ]);

        // make diet
//        $dietPlanModel = DietPlan::find($transaction->detail['diet_plan_id']);
//        if ($dietPlanModel){
//            app('dietService')->generateDiet($user ,$dietPlanModel , $transaction->detail['target_plan'] ?? null);
//        }

        // make diet with diet plan

        // remove alert when have free package
        $user->metas()->where('meta_key', UserMetaEnum::POPUP)->delete();

        $user->dispatchPaymentReminders($package->days);

        return $this->ok(
            [
                'status' => true,
                'user' => UserResource::make($transaction->user)
            ]
        );
    }

    public function packages(Request $request)
    {
        $user = $request->user();
        $dietType = $user->diet_type;
        $support = $request->get('with_support') ?? '1';
        if ($dietType == '') {
            return $this->badRequest('اطلاعات کاربر ناقص است');
        }
        if ($support == '1'){
            $packages = Package::active()->dietWithSupport()->orderByDesc('priority')->orderByDesc('id')->get();
        } else {
            $packages = Package::active()->diet()->orderByDesc('priority')->orderByDesc('id')->get();

        }
        if (!$packages) {
            return $this->badRequest('هیچ بسته ای برای شما یافت نشد. لطفا با پشتیبانی تماس بگیرید');
        }
        return $this->ok([
            'status' => true,
            'wallet' => $user->wallet,
            'expire_at' => 0,
            'package' => PackageResource::collection($packages)
        ]);
    }

    public function assginExersicePlan($user)
    {
        if ($user->how_much_experience_sports == 1 || $user->how_much_experience_sports == 0) {
            $user_level = 1;
        } else {
            $user_level = 2;
        }

        $exerse_forUser = ExercisePlanStrategy::where('gender', ExercisePlanStrategyGenderEnum::map((int)$user->gender))
            ->where('target', ExercisePlanStrategyTargetEnum::map((int)$user->target_of_exercise))
            ->where('level', ExercisePlanStrategyLevelEnum::map((int)$user_level))
            ->where('session_count', ((int)$user->how_many_days_week_exercise + 1))?->get();
        if ($exerse_forUser->isNotEmpty()) {
            //previous user plan
            $user_previous_plans = ExercisePlanRequest::where('user_id', $user->id)->pluck('exercise_plan_strategy_id');
            $hasNewPlan = Arr::flatten(array_diff($exerse_forUser->pluck('id')->toArray(), $user_previous_plans->toArray()));
            if (count($hasNewPlan) > 0) {
                $user_can_get = $hasNewPlan[0];
            } else {
                $user_can_get = ExercisePlanRequest::where('user_id', $user->id)
                    ->orderBy('created_at', 'asc')
                    ->whereNot('status', ExercisePlanRequestEnum::FAILED)?->first()?->id;
            }
        }
        //check if any plan exist for assigning to user
        if (!empty($user_can_get)) {
            $plan = ExercisePlanStrategy::find($user_can_get);
            //assign plan
            $chishod = $plan->makeRequest($user->id);
        }
    }

    public function assignDietPlan($user)
    {

        app('dietService')->computeCalorie($user);
        $conditions = app('dietService')->getUserConditions($user);
        $dietPlans = app('dietService')->getDietPlans($conditions);
        $dietPlan = app('dietService')->insertUserDietPlan($user, $dietPlans->first());
        if ($dietPlan['status']) {
            app('dietService')->makeRejim($dietPlan['dietRequestModel'], DietRequest::ACTION_REQUEST_INSERT);
        }
    }

    public function redirectToBank(Request $request)
    {
        $data = collect([
            'token' => $request->input('token'),
            'amount' => $request->input('amount'),
            'ref_num' => $request->input('ref_num'),
        ]);
        return view('api::redirect_to_bank', compact('data'));
    }

    public function verify(Request $request)
    {
        $transaction = Transaction::find($request->input('transaction_id'));
        $amount = $transaction->total_cost;
        if ($request->tracking_code && $request->card_number && $request->status) {
            $payStarPin = setting(SettingKeyEnum::PAYMENT_PAYSTAR_TOKEN);
            $payStarSign = setting(SettingKeyEnum::PAYMENT_PAYSTAR_SIGN);
            $stringToHash = $amount . '#' . $request->ref_num . '#' . $request->card_number . '#' . $request->tracking_code;
            $hashedString = hash_hmac('sha512', $stringToHash, $payStarSign);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $payStarPin,
                'Content-Type' => 'application/json',
            ])->timeout(30)
                ->post('https://core.paystar.ir/api/pardakht/verify', [
                    'ref_num' => $request->ref_num,
                    'amount' => $amount,
                    'sign' => $hashedString,
                ]);
            if ($response->json()) {

                if ($response->json()['status'] == 1) {
                    $detail = $transaction->detail;
                    $detail['payStar']['payment_result'] = serialize($response->json());
                    $transaction->update([
                        'detail' => $detail,
                        'status' => TransactionStatusEnum::SUCCESSFUL,
                    ]);
                } else {
                    $paymentStatus = false;
                    $message = $response->json()['message'];
                    return view('api::verify', compact('transaction', 'paymentStatus', 'message'));
                }
            }
        } else {
            $paymentStatus = false;
            $message = 'خطا در ایجاد تراکنش - لطفا مجدد اقدام کنید';
            return view('api::verify', compact('transaction', 'paymentStatus', 'message'));
        }
        return view('api::verify');
    }

    public function handleWallet(Transaction $transaction): void
    {
        if (isset($transaction->detail['user_wallet']) && $transaction->detail['user_wallet'] > 0){
            $user = $transaction->user;
            $detail = ['change_wallet_charge_by' => $user->id , 'transaction_id' => $transaction->id];
            WalletController::debit($user, $transaction->detail['user_wallet'],$detail);
        }
    }

    private function insertDietRequest($user)
    {
        $user->dietRequests()->create(
            [
                'diet_plan_id' => ''
            ]
        );
    }
}
