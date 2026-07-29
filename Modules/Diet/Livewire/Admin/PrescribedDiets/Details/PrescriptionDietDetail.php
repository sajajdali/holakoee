<?php

namespace Modules\Diet\Livewire\Admin\PrescribedDiets\Details;

use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Api\app\Resources\ReplaceFoodListResource;
use Modules\Diet\app\Services\DietDateSynchronizer;
use Modules\Diet\Entities\DietRequest;
use Modules\Diet\Entities\DietRequestDetail;
use Modules\Diet\Enum\DietRequestStatusEnum;
use Modules\Diet\Enum\FoodTypeEnum;
use Modules\Setting\Enum\SettingKeyEnum;
use Modules\User\app\Notifications\SmsNotification;

class PrescriptionDietDetail extends Component
{
    public $dietReqestId, $dietReqeust;
    //indicade that food_type is not simple and its combined ;
    public $foodTypeIsComined = false;

    public $isReplace = false;
    public $form = [];
    public $parentDietdetail = null;
    public $successFully = null;
    public bool $changeCalorie = false;
    public $color = [
        '',
        '#f8fcff'
    ];
    public $colorIndex;
    public array $replaceItems = [];
    public $selectedDetail = null;
    public ?int $selectedDetailId = null;

    //chart numbers
    public $totallMeals, $totaldays, $donedlMeals, $leftToConsume, $consumedMealPresent, $leftMealToConsume;

    public ?bool $replaceStatus = null;
    public ?string $replaceMessage = null;
    public bool $needExpertApproved = false;
    public bool $editingDates = false;

    public array $dateForm = [
        'start_date' => null,
        'end_date'   => null,
    ];
    public function openEditDatesModal(): void
    {
        // پیش‌فرض = تاریخ‌های فعلی رکورد (شمسی)
        $this->dateForm['start_jalali'] = $this->dietReqeust?->start_date
            ? verta($this->dietReqeust->start_date)->format('Y/m/d')
            : verta()->format('Y/m/d');

        $this->dateForm['end_jalali'] = $this->dietReqeust?->end_date
            ? verta($this->dietReqeust->end_date)->format('Y/m/d')
            : verta()->format('Y/m/d');

        $this->dispatch('open-edit-dates-modal', data: $this->dateForm);
    }
    public function toggleEditDates(): void
    {
        $this->successFully = null;

        $this->editingDates = ! $this->editingDates;

        // پر کردن فرم از مقدار فعلی
        $this->dateForm['start_date'] = optional($this->dietReqeust->start_date)->format('Y-m-d');
        $this->dateForm['end_date']   = optional($this->dietReqeust->end_date)->format('Y-m-d');

        // برای اینکه JS datepicker هم sync بشه
        $this->dispatch('dates-edit-opened', data: $this->dateForm);
    }

    public function cancelEditDates(): void
    {
        $this->editingDates = false;
        $this->reset('dateForm');
    }


    public function saveDates(): void
    {
        $this->validate([
            'dateForm.start_jalali' => ['required', 'regex:/^\d{4}\/\d{2}\/\d{2}$/'],
            'dateForm.end_jalali'   => ['required', 'regex:/^\d{4}\/\d{2}\/\d{2}$/'],
        ]);

        $start = Carbon::parse(
            Verta::parse(str_replace('/', '-', $this->dateForm['start_jalali']))->datetime()
        )->startOfDay();

        $end = Carbon::parse(
            Verta::parse(str_replace('/', '-', $this->dateForm['end_jalali']))->datetime()
        )->endOfDay();

        DB::transaction(function () use ($start, $end) {

            /** @var \Modules\Diet\Entities\DietRequest $diet */
            $diet = $this->dietReqeust->fresh();

            // فقط تاریخ‌ها را آپدیت کن
            $diet->update([
                'start_date' => $start,
                'end_date'   => $end,
            ]);

            DietDateSynchronizer::sync($diet);
        });

        $this->dispatch('close-edit-dates-modal');
        $this->dispatch('diet-sent', message: 'تاریخ‌ها با موفقیت به‌روزرسانی شدند.');
    }
    public function selectDetailForReplace(int $detailId): void
    {
        $this->selectedDetailId = $detailId;

        // برای نمایش متن بالای مودال
        $this->selectedDetail = DietRequestDetail::findOrFail($detailId);

        // گرفتن لیست جایگزین‌ها
        $result = $this->findDetailInDetails($detailId);

        $this->replaceStatus = (bool)($result['status'] ?? false);
        $this->replaceMessage = $result['message'] ?? null;

        // تبدیل کالکشن به آرایه ساده مخصوص رندر
        $this->replaceItems = [];
        if ($this->replaceStatus && isset($result['data'])) {
            $this->replaceItems = $result['data']
                ->map(fn($food) => ['id' => $food->id, 'name' => $food->name])
                ->values()
                ->toArray();
        }
    }

    protected $listeners = ['applyReplace']; // گوش دادن به رویداد applyReplace
    public function applyReplace(int $fromDetailId, int $toFoodId): void
    {
        $detail = DietRequestDetail::find($fromDetailId);

        if (!$detail) {
            $this->dispatch('diet-replaced', message: 'ردیف مورد نظر یافت نشد.');
            return;
        }

        $res = app('dietService')->replaceDietRequestDetail($detail, $toFoodId, auth()->id());
        if ($res['status']) {
            $this->dispatch('diet-replaced', message: 'وعده با موفقیت با غذای انتخابی جایگزین شد.');

        } else {
            $this->dispatch('diet-replaced', message: $res['message'] ?? 'ردیف مورد نظر یافت نشد.');
        }

    }

    public function loadReplaceList(): void
    {
        // بعدا اینو از دیتابیس/سرویس پر کن
        $this->replaceItems = [
            ['id' => 1, 'title' => 'مرغ گریل', 'cal' => 320],
            ['id' => 2, 'title' => 'ماهی بخارپز', 'cal' => 280],
            ['id' => 3, 'title' => 'استیک رژیمی', 'cal' => 350],
        ];
    }

    protected function findDetailInDetails(int $id)
    {
        $dietRequestDetail = DietRequestDetail::find($id);
        if (!$dietRequestDetail) {
            return [
                'status' => false,
                'message' => 'ردیف مورد نظر یافت نشد.',
            ];
        }

        $replaceFood = app('dietService')->replaceMealListFoodsV2($dietRequestDetail);
        if ($replaceFood->count() == 0) {
            return [
                'status' => false,
                'message' => 'غذایی برای جایگزینی یافت نشد',
                'data' => collect([]),
            ];
        }
        return [
            'status' => true,
            'message' => 'لیست غذاهای جایگزین',
            'data' => $replaceFood
        ];

    }

    protected function rules()
    {
        return [
            'form.new_calorie' => 'required',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'form.new_calorie' => 'کالری جدید',
        ];
    }


    #[Computed]
    public function details()
    {
        return DietRequestDetail::where('diet_request_id', $this->dietReqestId)->get()->groupBy('main_nutrition');

    }

    public function changeCalorieAction()
    {
        $this->validate();
        $this->dietReqeust->calories = (int)$this->form['new_calorie'];
        $this->dietReqeust->save();
        $this->changeCalorie = false;
        return $this->successFully = 'پلن با موفقیت تغییر کرد';
    }

    public function confirmByExpert()
    {
        $approvedAt = Carbon::now();
        $detail = $this->dietReqeust->detail;
        $detail['expert_approved'] = [
            'status' => true,
            'user_id' => auth()->user()->id,
            'date' => $approvedAt->toDateString(),
            'approved_at' => $approvedAt->toDateTimeString(),
        ];
        $this->dietReqeust->update([
            'detail' => $detail,
            'expert_approved_by' => auth()->id(),
            'expert_approved_at' => $approvedAt,
        ]);
        $this->needExpertApproved = false;
        $this->dispatch('diet-sent', message: 'تایید غذاها توسط شما با موفقیت انجام شد');
    }

    public function confirmAndSendDiet()
    {
        $dietCount = $this->dietReqeust->dietPlan->day_count;
        $this->dietReqeust->update([
            'sender_id' => auth()->user()->id,
            'status' => DietRequestStatusEnum::ACTIVE,
            'start_date' => Carbon::now()->toDateTimeString(),
            'end_date' => Carbon::now()->addDays($dietCount)->toDateTimeString(),
            'send_at' => Carbon::now()->toDateTimeString(),
        ]);
        DietDateSynchronizer::sync($this->dietReqeust);

        $template = setting(SettingKeyEnum::SMS_AFTER_SEND_DIET_FINALITY);
        if ($template) {
            $sendParameter = [
                $this->dietReqeust->user->first_name,
            ];
            $this->dietReqeust->user->notify(new SmsNotification($template, $sendParameter));
        }
        $this->dispatch('diet-sent', message: 'رژیم تجویز و برای کاربر ارسال شد');

    }

    public function toggleChangeCalorie()
    {
        $this->successFully = null;
        $this->changeCalorie = !$this->changeCalorie;
    }


    #[Computed]
    public function complexFoodResult($detail, $showMaxAllow = false): array
    {
        return app('dietService')->getComplexFood($detail, $showMaxAllow);
    }

    public function caculateColorAndParent($detail)
    {
        $index = 0;
        $index = $detail->day_number;
        if ($index > count($this->color)) {
            while (true) {
                $index = $index - count($this->color);
                if ($index <= count($this->color)) {
                    break;
                }
            }
        }
        return $index;
    }

    public function isReplaced($detail)
    {
        $this->isReplace = false;
        if ($detail->replaced_parent_id != null) {

            $this->isReplace = true;
        }

        return $this->isReplace;

    }

    public function parentDietdetail($detail_parent)
    {
        $parentd = DietRequestDetail::find($detail_parent->replaced_parent_id);
        return $parentd;
    }


    public function mount()
    {
        $this->dietReqeust = DietRequest::find($this->dietReqestId);

        // need expert approved
        if( isset($this->dietReqeust->dietPlan->detail['need_expert_approval']) && !isset($this->dietReqeust->detail['expert_approved'])) {
            $this->needExpertApproved = true;
        }

        $this->computeChart();
        $this->isThisCombineFood();
    }

    public function computeChart()
    {
        if ($this->dietReqeust->dietPlan) {
            $this->totaldays = $this->dietReqeust->dietPlan->day_count;
        } else {
            $this->totaldays = 0;
        }
        $this->totallMeals = DietRequestDetail::where('diet_request_id', $this->dietReqestId)->get()->count();
        $this->donedlMeals = DietRequestDetail::where('diet_request_id', $this->dietReqestId)->where('is_done', 1)->get()->count();
        if ($this->totallMeals && $this->donedlMeals) {
            $this->leftToConsume = $this->totallMeals - $this->donedlMeals;
            $this->consumedMealPresent = intval(($this->totallMeals - $this->donedlMeals) / $this->totallMeals * 100);
            $this->leftMealToConsume = intval(ceil(($this->totallMeals - ($this->totallMeals - $this->donedlMeals)) / $this->totallMeals * 100));
        }
    }


    #[Computed]
    public function allDetails()
    {
        return \Modules\Diet\Entities\DietRequestDetail::with(['meal', 'foodClass', 'foodable'])
            ->where('diet_request_id', $this->dietReqestId)
            ->join('meals', 'diet_request_details.meal_id', '=', 'meals.id') // 👈 join برای priority
            ->orderBy('diet_request_details.day_number')
            ->orderBy('meals.priority') // 👈 ترتیب وعده‌ها بر اساس priority
            ->select('diet_request_details.*') // فقط فیلدهای جدول اصلی
            ->get()
            ->groupBy('main_nutrition');
    }

    public function isThisCombineFood()
    {
        if ($this->dietReqeust->dietPlan && $this->dietReqeust->dietPlan->food_type == FoodTypeEnum::COMBINED) {
            $this->foodTypeIsComined = true;
        }
    }

    public function render()
    {
        return view('diet::livewire.admin.prescribed-diets.details.prescription-diet-detail');
    }
}
