<?php

namespace Modules\User\Livewire\Admin\User;

use Livewire\Component;
use Modules\Diet\Service\DietService;
use Modules\User\Entities\User;
use Modules\User\Entities\UserMeta;
use Modules\User\Enum\UserMetaEnum;
use Modules\Diet\Entities\DietPlan;
use Modules\User\app\Notifications\SmsNotification;
use Modules\Setting\Enum\SettingKeyEnum;


use Modules\Core\Entities\Disease;
use Modules\Diet\Entities\Condition;
use Modules\Diet\Enum\ConditionKeyEnum;
class CompleteRegistration extends Component
{
    public User $user;
    public bool $assignDietEnabled = false;

    public $suggestedDiet = null;
    public $selectedDiet = null;
    public bool $sendSmsAfterSendDiet = false;
    public bool $hasActivePackage = false;

    public array $form = [
        'first_name' => '',
        'last_name' => '',
        'diet_type' => null,
        'gender' => null,
        'athlete_or_not' => null,
        'activity_per_week' => 1,
        'food_restriction' => [],
        'birthday' => ['year' => null, 'month' => null, 'day' => null],
        'food_allergy' => [],
        'diseases' => [],
        'tall' => null,
        'weight' => null,
        'target_weight' => null,
        'weight_change_per_week' => 1,
        'invitation_code' => '',
        'target_plan' => 10, // پیشفرض
        'action' => 1,
    ];

    // گزینه‌ها (برای UI)
    public array $dietTypeOptions = [
        0 => 'کاهش وزن',
        1 => 'افزایش وزن',
        2 => 'تثبیت وزن',
    ];

    public array $genderOptions = [
        0 => 'مرد',
        1 => 'زن',
    ];

    public array $athleteOptions = [
        0 => 'ورزشکار نیستم',
        1 => 'ورزشکار هستم',
    ];

    public array $activityOptions = [
        0 => 'فعالیت فیزیکی بسیار کم',
        1 => 'فعالیت فیزیکی کم',
        2 => 'فعالیت فیزیکی متوسط',
        3 => 'فعالیت فیزیکی زیاد',
        4 => 'فعالیت فیزیکی بسیار زیاد',
    ];

    public array $weightChangeOptions = [
        0 => 'کم',
        1 => 'متوسط',
        2 => 'زیاد',
    ];

    public array $targetPlanOptions = [
        10 => 'رژیم',
        20 => 'ورزش',
        30 => 'ورزش و رژیم',
    ];

    // توجه: لیست شما تکراری داشت؛ اینجا Unique و مرتبش کردم
    public array $foodRestrictionOptions = [];

    public array $foodAllergyOptions = [ ];

    // این باید از سرور بیاد (فعلاً نمونه/placeholder)
    public array $diseaseOptions = [];
    public $dietPlans = [];
    public $packageExpireAt = '';

    private function loadFoodRestrictionOptions(): void
    {
        $condition = Condition::where('key', ConditionKeyEnum::FOOD_RESTRICTION->value)->first();

        if (!$condition) {
            $this->foodRestrictionOptions = [];
            return;
        }

        $values = data_get($condition->options, 'items.values', []);
        $this->foodRestrictionOptions = collect($values)
            ->mapWithKeys(fn($title, $index) => [(int)$index => (string)$title])
            ->toArray();
    }

    private function loadFoodAllergyOptions(): void
    {
        $condition = Condition::where('key', ConditionKeyEnum::FOOD_ALLERGY->value)->first();

        if (!$condition) {
            $this->foodAllergyOptions = [];
            return;
        }

        $values = data_get($condition->options, 'items.values', []);
        $this->foodAllergyOptions = collect($values)
            ->mapWithKeys(fn($title, $index) => [(int)$index => (string)$title])
            ->toArray();
    }

    private function loadDiseaseOptions(): void
    {
        // دقیقاً مثل هاردکد قبلی: [id => name]
        $this->diseaseOptions = Disease::active()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn($name, $id) => [(int)$id => (string)$name])
            ->toArray();
    }
    public function updatedAssignDietEnabled($value)
    {
        if ($value) {
            $this->findSuggestedDiet(); // این خودش selectedDiet رو ست می‌کنه
        } else {
            $this->selectedDiet = null;
            $this->sendSmsAfterSendDiet = false;
        }
    }

    public function findSuggestedDiet()
    {
        $conditions = app('dietService')->getUserConditions($this->user, true);
        $dietPlans  = app('dietService')->getDietPlans($conditions);

        $this->suggestedDiet = $dietPlans->first()?->id;
        $this->selectedDiet  = $this->suggestedDiet;
    }

    private function handleBirthdayParts(): array
    {
        $raw = $this->user->birthday ?? null;

        if (!$raw) {
            return ['year' => null, 'month' => null, 'day' => null];
        }

        $data = is_array($raw) ? $raw : json_decode($raw, true);

        if (!is_array($data)) {
            return ['year' => null, 'month' => null, 'day' => null];
        }

        return [
            'year'  => isset($data['year']) ? (int)$data['year'] : null,
            'month' => isset($data['month']) ? (int)$data['month'] : null,
            'day'   => isset($data['day']) ? (int)$data['day'] : null,
        ];
    }

    private function normalizeMetaValue(string $fieldKey, ?string $value)
    {
        if ($value === null) return null;

        $v = trim($value);

        // بعضی فیلدها عددی هستن
        $castIntFields = [
            'diet_type', 'gender', 'athlete_or_not', 'activity_per_week',
            'weight_change_per_week', 'target_plan', 'action',
        ];

        // اگه json باشه (birthday / arrays)
        if ($v !== '' && ($v[0] === '{' || $v[0] === '[')) {
            $decoded = json_decode($v, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // birthday در DB به شکل {"year":...,"month":...,"day":...} ذخیره شده
                if ($fieldKey === 'birthday') {
                    return [
                        'year'  => (int)($decoded['year'] ?? null),
                        'month' => (int)($decoded['month'] ?? null),
                        'day'   => (int)($decoded['day'] ?? null),
                    ];
                }
                // سایر آرایه‌ها مثل food_restriction/food_allergy
                return $decoded;
            }
        }

        if (in_array($fieldKey, $castIntFields, true)) {
            return $v === '' ? null : (int)$v;
        }

        // float ها
        $castFloatFields = ['weight', 'target_weight', 'tall'];
        if (in_array($fieldKey, $castFloatFields, true)) {
            return $v === '' ? null : (float)$v;
        }

        return $v;
    }

    private function loadFormFromMetas(): void
    {
        // تمام متاها: meta_key(int enum) => meta_value(string)
        $metas = $this->user->metas()
            ->select(['meta_key', 'meta_value'])
            ->get();

        $filled = [];

        foreach ($metas as $meta) {
            // meta_key عدد است => enum
            $enum = UserMetaEnum::tryFrom((int)$meta->meta_key->value);
            if (!$enum) continue;

            // تبدیل به key فرم: diet_type, food_allergy, ...
            $fieldKey = strtolower($enum->name);

            // فقط اگر داخل فرم داریم پر کن
            if (!array_key_exists($fieldKey, $this->form)) {
                continue;
            }

            $filled[$fieldKey] = $this->normalizeMetaValue($fieldKey, $meta->meta_value);
        }

        // diseases از pivot
        $filled['diseases'] = $this->user->diseases()->pluck('id')->map(fn($id)=> (int)$id)->values()->all();

        // merge امن
        $this->form = array_replace_recursive($this->form, $filled);

        // اطمینان از نوع آرایه‌ها برای checkbox ها
        $this->form['food_restriction'] = array_values(array_map('intval', (array)($this->form['food_restriction'] ?? [])));
        $this->form['food_allergy']     = array_values(array_map('intval', (array)($this->form['food_allergy'] ?? [])));
        $this->form['diseases']         = array_values(array_map('intval', (array)($this->form['diseases'] ?? [])));

        // birthday همیشه آرایه بمونه
        if (!is_array($this->form['birthday'] ?? null)) {
            $this->form['birthday'] = ['year' => null, 'month' => null, 'day' => null];
        }
    }

    public function mount()
    {
        $userParam = request()->route('user');
        $this->user = $userParam instanceof User ? $userParam : User::findOrFail($userParam);


        $this->loadFoodRestrictionOptions();
        $this->loadFoodAllergyOptions();
        $this->loadDiseaseOptions();

        // 1) اول: همه چیز از metas + diseases بیاد
        $this->loadFormFromMetas();

        // 2) اگر ستون‌های مستقیم user هم داری و می‌خوای اولویت داشته باشن:
        $this->form['first_name'] = $this->user->first_name ?? $this->form['first_name'] ?? '';
        $this->form['last_name']  = $this->user->last_name  ?? $this->form['last_name'] ?? '';
        $this->form['gender']     = $this->user->gender     ?? $this->form['gender'] ?? null;
        $this->dietPlans = \Modules\Diet\Entities\DietPlan::where('general_pattern', 0)->get();
        if ($this->user->activePackage()){
            $this->packageExpireAt = verta($this->user->activePackage()->end_at)->format('%d %B %Y');
            $this->hasActivePackage = true;
        }

    }

    protected function rules(): array
    {
        return [
            'form.first_name' => ['required', 'string', 'min:2', 'max:80'],
            'form.last_name'  => ['required', 'string', 'min:2', 'max:80'],

            'form.gender' => ['required', 'integer', 'in:0,1'],

            // هدف شما
            'form.diet_type' => ['required', 'integer', 'in:0,1,2'],

            // فعالیت روزانه (کلیدهای options شما 0..4 هست)
            'form.activity_per_week' => ['required', 'integer', 'in:0,1,2,3,4'],

            // تاریخ تولد شمسی (سه‌تکه)
            'form.birthday.year'  => ['required', 'integer', 'between:1200,1500'],
            'form.birthday.month' => ['required', 'integer', 'between:1,12'],
            'form.birthday.day'   => ['required', 'integer', 'between:1,31'],

            // قد و وزن‌ها
            'form.tall'          => ['required', 'numeric', 'between:50,250'],
            'form.weight'        => ['required', 'numeric', 'between:20,400'],
            'form.target_weight' => ['required', 'numeric', 'between:20,400'],

            // بقیه فیلدها اگر خواستی اجباری نباشن همین‌طور بمونه
            'form.athlete_or_not'        => ['nullable', 'integer', 'in:0,1'],
            'form.weight_change_per_week'=> ['nullable', 'integer', 'in:0,1,2'],
        ];
    }

    protected array $messages = [
        'form.first_name.required' => 'نام الزامی است.',
        'form.last_name.required'  => 'نام خانوادگی الزامی است.',

        'form.gender.required'     => 'جنسیت الزامی است.',
        'form.gender.in'           => 'جنسیت نامعتبر است.',

        'form.diet_type.required'  => 'هدف شما الزامی است.',
        'form.diet_type.in'        => 'هدف انتخاب‌شده نامعتبر است.',

        'form.activity_per_week.required' => 'فعالیت روزانه الزامی است.',
        'form.activity_per_week.in'       => 'فعالیت روزانه نامعتبر است.',

        'form.birthday.year.required'  => 'سال تولد الزامی است.',
        'form.birthday.month.required' => 'ماه تولد الزامی است.',
        'form.birthday.day.required'   => 'روز تولد الزامی است.',

        'form.tall.required' => 'قد الزامی است.',
        'form.tall.between'  => 'قد باید بین 50 تا 250 سانتی‌متر باشد.',

        'form.weight.required' => 'وزن اولیه الزامی است.',
        'form.weight.between'  => 'وزن باید بین 20 تا 400 کیلوگرم باشد.',

        'form.target_weight.required' => 'وزن هدف الزامی است.',
        'form.target_weight.between'  => 'وزن هدف باید بین 20 تا 400 کیلوگرم باشد.',
    ];

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $y = (int) data_get($this->form, 'birthday.year', 0);
            $m = (int) data_get($this->form, 'birthday.month', 0);
            $d = (int) data_get($this->form, 'birthday.day', 0);

            // ✅ چک معتبر بودن تاریخ شمسی با verta
            try {
                // اگر تاریخ نامعتبر باشه Exception می‌خوره
                verta()->setDate($y, $m, $d);

                // (اختیاری) چک تاریخ آینده نباشه
                // اگر نمی‌خوای حذفش کن
                if (verta()->setDate($y, $m, $d)->greaterThan(verta())) {
                    $validator->errors()->add('form.birthday.year', 'تاریخ تولد نمی‌تواند در آینده باشد.');
                }

            } catch (\Throwable $e) {
                $validator->errors()->add('form.birthday.year', 'تاریخ تولد شمسی نامعتبر است.');
            }

            // ✅ سازگاری وزن هدف با هدف (همون قبلی)
            $dietType = $this->form['diet_type'] ?? null; // 0 کاهش، 1 افزایش، 2 تثبیت
            $weight   = (float)($this->form['weight'] ?? 0);
            $target   = (float)($this->form['target_weight'] ?? 0);

            if ($dietType === 0 && $target >= $weight) {
                $validator->errors()->add('form.target_weight', 'برای کاهش وزن، وزن هدف باید کمتر از وزن اولیه باشد.');
            }

            if ($dietType === 1 && $target <= $weight) {
                $validator->errors()->add('form.target_weight', 'برای افزایش وزن، وزن هدف باید بیشتر از وزن اولیه باشد.');
            }

            if ($dietType === 2 && abs($target - $weight) > 0.5) {
                $validator->errors()->add('form.target_weight', 'برای تثبیت وزن، وزن هدف باید نزدیک وزن اولیه باشد.');
            }
        });
    }
    public function submit()
    {
        $this->validate();
        $user = $this->user;

        $payload = $this->form;

        // birthday به آرایه سه‌تایی برای سازگاری با منطق API
        $payload['birthday'] = [
            (int)($this->form['birthday']['year'] ?? 0),
            (int)($this->form['birthday']['month'] ?? 0),
            (int)($this->form['birthday']['day'] ?? 0),
        ];

        // diseases pivot
        if (isset($payload['diseases']) && is_array($payload['diseases'])) {
            $user->diseases()->sync($payload['diseases']);
        }
        unset($payload['diseases']);

        // target_plan طبق API
        $payload['target_plan'] = 10;

        // metas
        foreach (UserMetaEnum::keys() as $key) {

            $fieldKey = strtolower($key->name);

            if (!array_key_exists($fieldKey, $payload)) {
                continue;
            }

            $value = $payload[$fieldKey];

            if (is_array($value) || is_object($value)) {

                if ($fieldKey === 'birthday') {
                    $value = [
                        'year'  => (int)($value[0] ?? 0),
                        'month' => (int)($value[1] ?? 0),
                        'day'   => (int)($value[2] ?? 0),
                    ];
                }

                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            if ($fieldKey === 'athlete_or_not') {
                $value = (int)$value;
            }

            // ✅ جلوگیری از تکرار: updateOrCreate
            UserMeta::updateOrCreate(
                [
                    'user_id'   => $user->id,
                    'meta_key'  => $key->value,
                ],
                [
                    'meta_value' => $value,
                ]
            );
        }

        // newly registered
        UserMeta::updateOrCreate(
            ['user_id' => $user->id, 'meta_key' => UserMetaEnum::NEWLY_REGISTERED->value],
            ['meta_value' => '1']
        );

        /** -------------------------
        send diet
         * --------------------------*/
        if ($this->assignDietEnabled) {

            $this->validate([
                'selectedDiet' => 'required'
            ], [
                'selectedDiet.required' => 'لطفا رژیم مورد نظر را انتخاب کنید',
            ]);

            $diet_plan = DietPlan::find($this->selectedDiet);

            if ($diet_plan) {
                $freshUser = $user->fresh();
                app('dietService')->generateDiet($freshUser, $diet_plan, $user->dietPlan);

                if ($this->sendSmsAfterSendDiet) {
                    $template = setting(SettingKeyEnum::SMS_AFTER_SEND_DIET);
                    if ($template) {
                        $sendParameter = [$freshUser->first_name];
                        $user->notify(new SmsNotification($template, $sendParameter));
                    }
                }
            }
        }

        session()->flash('success', 'اطلاعات با موفقیت ذخیره و درخواست رژیم ثبت شد.');

        // اگر رژیم هم داد، معمولاً بهتره بره صفحه document
        if ($this->assignDietEnabled) {
            return redirect()->route('admin.user.index');
        }

        return redirect()->route('admin.user.index');
    }
    public function render()
    {
        return view('user::livewire.admin.user.complete-registration');
    }
}
