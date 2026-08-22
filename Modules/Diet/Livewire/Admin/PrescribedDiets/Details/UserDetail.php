<?php

namespace Modules\Diet\Livewire\Admin\PrescribedDiets\Details;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Modules\User\Entities\User;
use Modules\Core\Entities\Disease;
use Modules\Diet\Entities\DietRequest;
use Modules\Diet\Entities\DietRequestDetail;
use Modules\User\Enum\UserMetaEnum;

class UserDetail extends Component
{
    public $dietReqestId;
    public ?array $userInfo;
    public User $assignedUser;
     public ?array $disease ;
    public $tall;
    public $currentWeight;
    public bool $isEditingMeasurements = false;
    public ?string $successFully = null;

    protected $rules = [
        'tall' => 'required|numeric|between:50,300',
        'currentWeight' => 'required|numeric|between:20,500',
    ];

    protected $messages = [
        'tall.required' => 'قد را وارد کنید.',
        'tall.numeric' => 'قد باید عدد باشد.',
        'tall.between' => 'قد باید بین ۵۰ تا ۳۰۰ سانتی‌متر باشد.',
        'currentWeight.required' => 'وزن را وارد کنید.',
        'currentWeight.numeric' => 'وزن باید عدد باشد.',
        'currentWeight.between' => 'وزن باید بین ۲۰ تا ۵۰۰ کیلوگرم باشد.',
    ];


    public function disease() {
        if(! empty($this->userInfo['DISEASES']))  {
            foreach ($this->userInfo['DISEASES'] as $key => $diseasseId) {
                $this->disease[] = Disease::where('id',$diseasseId)->first()->name ;
            }
        }
    }
    public function mount()
    {
        $dietRequest = (DietRequest::find($this->dietReqestId));
        $this->userInfo = $dietRequest->user_information;
        $this->assignedUser = $dietRequest->user;
        $this->tall = $this->userInfo['TALL'] ?? $this->assignedUser->tall;
        $this->currentWeight = $this->userInfo['CURRENT_WEIGHT'] ?? $this->assignedUser->current_weight;

        $this->disease();
        //  dd( $this->userInfo);
    }

    public function editMeasurements(): void
    {
        $this->successFully = null;
        $this->isEditingMeasurements = true;
    }

    public function cancelEditingMeasurements(): void
    {
        $this->tall = $this->userInfo['TALL'] ?? $this->assignedUser->tall;
        $this->currentWeight = $this->userInfo['CURRENT_WEIGHT'] ?? $this->assignedUser->current_weight;
        $this->resetValidation();
        $this->isEditingMeasurements = false;
    }

    public function saveMeasurements(): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            $dietRequest = DietRequest::findOrFail($this->dietReqestId);
            $user = $dietRequest->user;

            $user->setMetas(UserMetaEnum::TALL, $validated['tall']);
            $user->setMetas(UserMetaEnum::CURRENT_WEIGHT, $validated['currentWeight']);

            $userInformation = $dietRequest->user_information ?? [];
            $userInformation['TALL'] = $validated['tall'];
            $userInformation['CURRENT_WEIGHT'] = $validated['currentWeight'];
            $dietRequest->update(['user_information' => $userInformation]);

            $this->assignedUser = $user->fresh();
            $this->userInfo = $userInformation;
        });

        $this->isEditingMeasurements = false;
        $this->successFully = 'قد و وزن کاربر با موفقیت ویرایش شد.';
    }


    public function render()
    {
        return view('diet::livewire.admin.prescribed-diets.details.user-detail');
    }
}
