<?php

namespace Modules\Api\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Api\Entities\AuthRequest;
use Modules\Api\Entities\UserDevice;
use Modules\Api\Enum\PopupEnum;
use Modules\Api\Enum\RouteEnum;
use Modules\Api\Enum\UserDeviceTypeEnum;
use Modules\Api\Http\Requests\LoginRequest;
use Modules\Api\Http\Requests\RegisterRequest;
use Modules\Api\Trait\ApiHandlerTrait;
use Modules\Api\Transformers\UserResource;
use Modules\Package\Entities\Package;
use Modules\Package\Enum\PackageUserTypeEnum;
use Modules\Setting\Enum\SettingKeyEnum;
use Modules\User\Entities\User;
use Modules\User\Entities\UserMeta;
use Modules\User\Enum\UserMetaEnum;

class AuthController extends Controller
{
    use ApiHandlerTrait;

    const STATUS_VERIFY = 1;
    const STATUS_REGISTER = 2;

    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {

        $accessToken = $request->bearerToken();
        $token = PersonalAccessToken::findToken($accessToken);
        if ($token) {
            UserDevice::where('access_token_id', $token->id)->delete();
        }
        $request->user()->currentAccessToken()->delete();

        return $this->ok(['message' => 'success']);
    }

    public function verify(RegisterRequest $request): \Illuminate\Http\JsonResponse
    {
        $code = $request->input('code');
        $emailOrMobile = $request->input('mobile') ?? $request->input('mobile_or_email'); //input is validated in AuthRequestCode
        if (AuthRequest::check($emailOrMobile, $code)) {//token become checked in check method
            $user = AuthRequest::getUser($emailOrMobile); //create or get user
            $token = $user->createToken(Str::uuid())->plainTextToken; //create new token
            $tokenModel = PersonalAccessToken::findToken($token);
            UserDevice::create([
                'user_id' => $user->id,
                'access_token_id' => $tokenModel?->id,
                'type' => UserDeviceTypeEnum::tryFrom($request->input('device_os')) ?? UserDeviceTypeEnum::getDefault(),
                'fcm_token' => $request->input('fcm_token'),
                'device_version' => $request->input('device_version'),
                'device_info' => $request->json('device_info', []),
                'ip' => ip(),
            ]);

            return $this->ok([
                'message' => 'success',
                'is_old_user' => isset($user->weight),
                'token' => $token,
                'user' => UserResource::make($user),
            ]);
        }

        return $this->badRequest(data: ['message' => 'کد وارد شده صحیح نمی باشد']);
    }

    public function storeDevice(Request $request)
    {
        $user = auth()->user();
        $token = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $token);
        $tokenModel = PersonalAccessToken::findToken($token);
        UserDevice::create([
            'user_id' => $user->id,
            'access_token_id' => $tokenModel?->id,
            'type' => UserDeviceTypeEnum::tryFrom($request->input('device_os')) ?? UserDeviceTypeEnum::getDefault(),
            'fcm_token' => $request->input('fcm_token'),
            'device_version' => $request->input('device_version'),
            'device_info' => $request->json('device_info', []),
            'ip' => ip(),
        ]);

        return $this->ok([
            'message' => 'success',
            'user_status' => $this->handleNextStep($user),
            'token' => $token,
            'user' => UserResource::make($user),
        ]);
    }

    private function handleNextStep($user)
    {
        if (!$user->gender) {
            return strtolower(RouteEnum::REGISTER->value);
        }

        return strtolower(RouteEnum::DASHBOARD->value);
    }
    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {

        $emailOrMobile = $request->input('mobile') ?? $request->input('mobile_or_email'); //input is validated in AuthRequestCode
        $device = $request->input('login_from');
        //check user can send new request
        if (AuthRequest::canRequest($emailOrMobile)) {
            //create new request
            AuthRequest::make($emailOrMobile, ip() , $device); //sms will send in make method

            return $this->created(['message' => 'success']);
        }

        return $this->tooManyRequest(message: 'برای ارسال مجدد باید یک دقیقه صبر کنید');
    }

    public function loginAndRegister(LoginRequest $request): \Illuminate\Http\JsonResponse
    {

        $emailOrMobile = $request->input('mobile') ?? $request->input('email'); //input is validated in AuthRequestCode
        //check user can send new request
        if (AuthRequest::canRequest($emailOrMobile)) {
            //create new request
            AuthRequest::make($emailOrMobile, ip()); //sms will send in make method

            return $this->created(['message' => 'success']);
        }

        return $this->tooManyRequest(message: 'برای ارسال مجدد باید یک دقیقه صبر کنید');
    }

    public function completeRegister(Request $request)
    {

        $user = auth()->user(); //create or get user


        // if statement
        if ($request->has('first_name')) {
            $statement = $request->all();
            $statement = json_decode(json_encode($statement));

            if (gettype($statement) <> "object") {
                return $this->badRequest(data: ['message' => 'دیتای ارسالی با فرمت اشتباه ارسال شده است']);
            }
            if (property_exists($statement , 'diet_type')){
                $statement->diet_plan = $statement->diet_type;
            }
            // invitation code
            if (!empty($statement->invitation_code)) {
                $invitingUserMeta =
                    UserMeta::where('meta_key', UserMetaEnum::INVITATION_CODE)
                        ->where('meta_value', $statement->invitation_code)
                        ->first()?->user_id;

                if ($invitingUserMeta) {
                    User::find($invitingUserMeta)->inviteFriends()->firstOrCreate(
                        [
                            'user_invited_id' => $user->id,
                            'benefit' => 0,
                        ]
                    );
                }
                unset($statement->invitation_code);
            }
            $listMetas = [];

            //  first name
            if (!empty($statement->first_name)) {
                $listMetas[] = new UserMeta([
                    'meta_key' => UserMetaEnum::FIRST_NAME,
                    'meta_value' => $statement->first_name
                ]);
                unset($statement->first_name);
            }

            //  last name
            if (!empty($statement->last_name)) {
                $listMetas[] = new UserMeta([
                    'meta_key' => UserMetaEnum::LAST_NAME,
                    'meta_value' => $statement->last_name
                ]);

                unset($statement->last_name);
            }

            if ( property_exists($statement , 'invitation_code') && $statement->invitation_code == "" ) {
                unset($statement->invitation_code);
            }

            // insert user diseases
            if (isset($statement->diseases) && $statement->diseases <> null) {
                $userDiseases = $statement->diseases;
                if (count($userDiseases) && is_array($userDiseases)) {
                    $user->diseases()->sync($userDiseases);
                }
            }
            unset($statement->diseases);
            // insert user diseases

            // set default target plan to diet
//            if ( !property_exists($statement , 'target_plan') || $statement->target_plan == "" ) {
//                $statement->target_plan = 10;
//            }

            // Method to achieve the goal (with diet)
            $statement->target_plan = 10;

            foreach (UserMetaEnum::keys() as $key) {
                $fieldKey = strtolower($key->name);
                if (property_exists($statement, $fieldKey)) {
                    $value = $statement->{$fieldKey};
                    if (is_array($value) || is_object($value)) {
                        if($fieldKey == 'birthday'){
//                            $value->day += 1;
                            $birthDay['year'] = (int) $value[0];
                            $birthDay['month'] = (int) $value[1];
                            $birthDay['day'] = (int) $value[2];
                            $value = $birthDay;
                        }
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }
                    if ($fieldKey == 'athlete_or_not'){
                        $value = (int)$value;
                    }
                    $listMetas[] = new UserMeta([
                        'meta_key' => UserMetaEnum::tryFrom($key->value),
                        'meta_value' => $value
                    ]);
                }
            }
            $listMetas[] = new UserMeta([
                'meta_key' => UserMetaEnum::NEWLY_REGISTERED,
                'meta_value' => '1'
            ]);

            // insert popup free account
//            $listMetas[] = new UserMeta([
//                'meta_key' => UserMetaEnum::POPUP,
//                'meta_value' => PopupEnum::FREE_ACCOUNT->value
//            ]);

            if (count($listMetas)) {
                $user->metas()->saveMany($listMetas);
            }

            if ($freeRecharge = (int) setting(SettingKeyEnum::FREE_RECHARGE)) {
                if ($freeRecharge > 0) {
                    if ($user->wallet == 0){
                        $user->increaseWallet($freeRecharge);
                    }
                }
            }



            // assign free package
//            $freePackage = Package::find(4);
//            $user->packages()->create([
//                'package_id' => $freePackage->id,
//                'start_at' => Carbon::now()->toDateString(),
//                'end_at' => Carbon::now()->addDays(2 ?? 0)->toDateString(),
//                'type' => PackageUserTypeEnum::IN_USE,
//            ]);

        }
        // if statement

        // get landing data

        $user->dispatchRegistrationReminders();

        //  send user notification after register
        $notificationBody = setting(SettingKeyEnum::NOTIFICATION_AFTER_LOGIN);
        if (isset($notificationBody)){
            $param = [$user->first_name];
            sendNotification($user ,$param ,$notificationBody);
        }

        return $this->ok(data: [
            'message' => 'اطلاعات با موفقیت ذخیره شد',
        ]);
    }


}


