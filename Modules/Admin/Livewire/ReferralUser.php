<?php

namespace Modules\Admin\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Api\Entities\AuthRequest;
use Modules\User\Entities\User;
use Modules\User\Enum\UserMetaEnum;

class ReferralUser extends Component
{

    public User $user;
    public $page = 'referral';
    public $applicationLink = '';

    public $mobile = '';

    public $code = '';
    public $sendAgain = '';
    public array $number = [];


    public function mount()
    {
//        $this->page = 'redirect_to_app';
        $referralCode =  request()->route('referral');
        $referral = User::whereHas('metas', function ($query) use ($referralCode) {
            $query->where('meta_key', UserMetaEnum::INVITATION_CODE)
                ->where('meta_value', $referralCode);
        })->first();
        if ($referral){
            $this->user = $referral;
        } else {
            return redirect('https://webapp.drholakoo.ir');
        }
    }


    public function sendCode()
    {
        $this->mobile = convertToLatinNumbers($this->mobile);
        $this->validate([
            'mobile' => 'required|numeric|digits:11|regex:/^09\d{9}$/', // 11 رقم و با 09 شروع بشه
        ]);
        if (AuthRequest::canRequest($this->mobile)) {
            AuthRequest::make($this->mobile, ip()); //sms will send in make method
            $this->page = 'enter_code';
        } else {
            $this->addError('mobile', 'حداقل ۲ دقیقه صبر کنید برای ارسال مجدد');
        }

    }
    public function sendCodeAgain()
    {
        $this->sendAgain = null;
        $this->dispatch('resetTimer');

        if (AuthRequest::canRequest($this->mobile)) {
            AuthRequest::make($this->mobile, ip());
        } else {
            $this->sendAgain = 'حداقل ۲ دقیقه صبر کنید برای ارسال مجدد';
        }
    }

    public function submitCode()
    {
        $this->sendAgain = '';
        $thisCode = implode('', $this->number);

        if (strlen($thisCode) < 4){
            return $this->sendAgain = 'لطفا ۴ عدد رو وارد کنید';
        }


        if (AuthRequest::check($this->mobile, $thisCode)) {

            $user = AuthRequest::getUser($this->mobile); //create or get user
            $user->tokens()->delete();
            $token = $user->createToken('mobile_app')->plainTextToken; //create new token

            //set referral colde
            $this->user->inviteFriends()->firstOrCreate(
                [
                    'user_invited_id' => $user->id,
                    'benefit' => 0,
                ]
            );
            $this->page = 'redirect_to_app';
            $this->applicationLink = 'http://webapp.drholakoo.ir/sso?token=' . $token;
//            return redirect('http://webapp.drholakoo.ir/sso?token=' . $token, );
        } else {
            $this->sendAgain = 'کد وارد شده اشتباه است';
        }
    }

    public function changePage($page)
    {
        $this->page = $page;
        $this->render();
    }

    #[Title('الان وقتشه شروع کنی')]
    #[Layout('admin::layouts.components.referral')]
    public function render()
    {
        return view('admin::livewire.referral-user');
    }

}
