<?php

namespace Modules\User\Livewire\Admin\User\UserDocuments;

use Dompdf\Dompdf;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\User\Entities\User;

#[Title("پرونده کاربر")]
// #[Layout('admin::layouts.app')]
class Index extends Component
{
    public $user;
    public $first_name, $last_name, $mobile, $password, $gender;
    public $modaldata;
    public $userBmi;
    public $title = null;
    public $message = null;
    public $messages = null;
    public $referralStatics = [];
    public $mmsg = false;
    public $meta_is_json_array = false;
    public function editbasicInfo()
    {
        if (isset($this->first_name)) {
            $this->user->first_name = $this->first_name;
        }
        if (isset($this->last_name)) {
            $this->user->last_name = $this->last_name;
        }
        if (isset($this->mobile)) {
            $this->user->mobile = $this->mobile;
        }
        if (isset($this->gender)) {
            $this->user->gender = $this->gender;
        }
        if ($this->password) {
            $this->user->update(['password' => Hash::make($this->password)]);
        }
        $this->mmsg = 'اطلااعات کاربر با موفقیت ویرایش شد';
    }

    public function sendMessage()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ], [
            'title.required' => 'عنوان پیام الزامی است.',
            'title.max' => 'عنوان پیام نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',
            'message.required' => 'متن پیام الزامی است.',
        ]);

        $this->user->messages()->create([
            'title' => $this->title,
            'body' => $this->message,
        ]);

        session()->flash('success', 'پیغام با موفقیت ارسال شد.');

        // بازنشانی ورودی‌ها
        $this->reset(['title', 'message']);
        $this->loadMessages();


    }

    public function loadMessages()
    {
        $this->messages = $this->user->messages()->latest()->get();
    }
    public function luchModal($metaName)
    {
        $this->meta_is_json_array = false;
        $this->modaldata = $this->user->$metaName;
        $this->modaldata->name = $this->user->$metaName?->last()->meta_key->getName();
        if ($metaName == 'habits_meta' || $metaName ==  'food_restriction_meta' || $metaName ==  'weaknesses_body_meta') {
            $this->meta_is_json_array = true;
        }
        $this->dispatch('lunchHistoryModal');
    }
    public function mount()
    {
        $user = User::findOrFail(request()->route('user'));
        $this->messages   = $user->messages()->latest()->get();
        $this->user       = $user;
        $this->first_name =  $this->user->first_name;
        $this->last_name  =  $this->user->last_name;
        $this->mobile     =  $this->user->mobile;
        $this->gender     =  $this->user->gender;

        // referral
        $inviteFriends = $user->inviteFriends;
        $this->referralStatics = [
            'number_invited_friends' => $inviteFriends->count() ,
            'total_benefit' => number_format($inviteFriends->sum('benefit')),
            'successful_referrals' => number_format($inviteFriends->where('benefit' , '>' , '0')->count()),
        ];
    }

    public function calculateBmi()
    {
        $this->modaldata = null;
        $this->userBmi = app('dietService')->computeCalorie($this->user);
    }
    public function render()
    {
        return view('user::livewire.admin.user.user-documents.index');
    }
}
