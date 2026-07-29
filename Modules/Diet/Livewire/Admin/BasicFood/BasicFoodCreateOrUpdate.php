<?php

namespace Modules\Diet\Livewire\Admin\BasicFood;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Modules\Core\Entities\Disease;
use Modules\Diet\Entities\BasicFood;
use Modules\Diet\Entities\Condition;
use Modules\Diet\Entities\FoodCategory;
use Modules\Diet\Entities\FoodUnit;
use Modules\Diet\Enum\BasicFoodTypeEnum;
use Modules\Diet\Enum\ConditionApplyItemEnum;
use Modules\Diet\Enum\ConditionKeyEnum;
use Modules\Diet\Livewire\Admin\Traits\ConditionTrait;
use Illuminate\Support\Str;
use Modules\AI\Services\AiMenuService;
use Modules\AI\Services\TokenService;

class BasicFoodCreateOrUpdate extends Component
{
    use AuthorizesRequests, ConditionTrait;

    public ?BasicFood $basicFood = null;
    public $prevItem;
    public $nextItem;
    public bool $isEdited = false;
    public int $foodUnitCounter = 1;
    public int $foodCategoryCounter = 0;

    public array $fetchData = [];
    public array $form = [
        'name'               => '',
        'typeFood'           => false,
        'recipe'             => '',
        'foodFact'           => [],

        'food_unit'          => [],      // [rowKey => food_unit_id]
        'quantity_per_unit'  => [],      // [rowKey => grams]
        'max_allowed'        => [],      // [rowKey => int|null]
        'isPrimary'          => null,    // rowKey

        'foodUnits'          => [],      // [rowKey => Collection<FoodUnit>]
        'food_categories'    => [],      // [index => category_id]
        'conditions'         => [],      // [condition_id => [optionKey|diseaseId => true]]
    ];

    protected $messages = [
        'form.name.required' => 'نام غذا را وارد کنید',
    ];

    // ---------- AI: State ----------
    public array $aiForm = [
        'food_name' => '',
    ];
    public bool $aiHasTokens = true;
    public int  $aiTokenBalance = 0;
    public bool $aiForceRefresh = false;

    public bool $showCache = false;
    public array $cachedItems = [];  // [{key,title,ts}]
    public int   $cachedPage  = 1;
    public int   $cachedPerPage = 10;
    public int   $cachedTotal = 0;

    // ---------- Lifecycle ----------
    public function mount()
    {

        // fetch
        $this->fetchData['foodUnits']      = FoodUnit::all();
        $this->fetchData['foodCategories'] = FoodCategory::all();
        $this->fetchData['disease']        = Disease::active()->get();
        $this->fetchData['conditions']     = Condition::whereJsonContains('apply_to', ConditionApplyItemEnum::BASIC_FOOD->value)->get();

        // auto-checked for conditions (non-disease)
        foreach ($this->fetchData['conditions'] as $value) {
            if ($value->key == ConditionKeyEnum::DISEASE) continue;
            foreach ($value->options['checked'] as $k => $check) {
                $this->form['conditions'][$value->id][$value->options['items']['keys'][$k]] = $value->options['checked'][$k];
            }
        }

        $basicFood = request()->route('basic_food');
        if ($basicFood instanceof BasicFood) {
            $this->authorize('update', $basicFood);
            $this->isEdited  = true;
            $this->basicFood = $basicFood;

            $this->form['name']     = $basicFood->name ?? '';
            $this->form['typeFood'] = $basicFood->type == BasicFoodTypeEnum::COMBINED ?? false;
            $this->form['recipe']   = $basicFood->recipe ?? '';
            $this->form['foodFact'] = $basicFood->food_fact ?? [];

            // conditions pivot -> checkbox states
            foreach ($this->basicFood->conditions as $condition) {
                $this->form['conditions'][$condition->id] = $this->convertToAssociativeArray(json_decode($condition->pivot->options));
            }

            // units
            $units = $this->basicFood->units;
            if ($units->count()) {
                foreach ($units as $unit) {
                    if ($unit->pivot->is_primary == 1) {
                        $this->form['isPrimary'] = $this->foodUnitCounter;
                    }
                    $this->form['foodUnits'][$this->foodUnitCounter]           = $this->fetchData['foodUnits'];
                    $this->form['quantity_per_unit'][$this->foodUnitCounter]   = $unit->pivot->quantity_per_unit;
                    $this->form['max_allowed'][$this->foodUnitCounter]         = $unit->pivot->max_allowed;
                    $this->form['food_unit'][$this->foodUnitCounter]           = $unit->id;
                    $this->foodUnitCounter++;
                }
            } else {
                $this->form['foodUnits'][$this->foodUnitCounter] = $this->fetchData['foodUnits'];
            }

            // categories
            if ($this->basicFood->category()->count()) {
                $this->form['food_categories'] = $this->basicFood->category->pluck('id')->toArray();
                $this->foodCategoryCounter     = $this->basicFood->category()->count() - 1;
            } else {
                $this->form['food_categories'][0] = '';
            }
        } else {
            $this->form['foodUnits'][$this->foodUnitCounter] = $this->fetchData['foodUnits'];
            $this->form['food_categories'][0] = '';
        }

        // prev/next
        if (! is_null($this->basicFood) && $this->basicFood->exists) {
            $this->prevItem = BasicFood::where('id', '<', $this->basicFood->id)->orderBy('id', 'desc')->first()?->id;
            $this->nextItem = BasicFood::where('id', '>', $this->basicFood->id)->orderBy('id', 'asc')->first()?->id;
        }
    }

    public function render()
    {
        $title = (! is_null($this->basicFood) && $this->basicFood->exists) ? 'ویرایش غذا' : 'ایجاد غذا جدید';
        return view('diet::livewire.admin.basic-food.basic-food-create-or-update')->title($title);
    }

    // ---------- Repeaters ----------
    public function addFoodUnit(){ ++$this->foodUnitCounter; $this->form['foodUnits'][$this->foodUnitCounter] = $this->fetchData['foodUnits']; }
    public function removeFoodUnit($number){
        unset($this->form['foodUnits'][$number], $this->form['quantity_per_unit'][$number], $this->form['food_unit'][$number], $this->form['max_allowed'][$number]);
        if ($this->form['isPrimary'] === $number) $this->form['isPrimary'] = null;
    }
    public function addCategory(){ ++$this->foodCategoryCounter; $this->form['food_categories'][$this->foodCategoryCounter] = ''; }
    public function removeFoodCategory($number){
        --$this->foodCategoryCounter;
        unset($this->form['food_categories'][$number]);
        $this->form['food_categories'] = array_values($this->form['food_categories']);
    }

    public function updated($name, $value)
    {
        if (Str::startsWith($name, 'form.food_unit')) {
            // وقتی نوع واحد عوض می‌شود، max_allowed شفاف شود (اختیاری)
        }
    }

    // ---------- Validation ----------
    public function rules()
    {
        return [
            'form.name'                       => 'required|string|min:2|max:255',
            'form.foodFact'                   => 'required|array',
            'form.foodFact.CALORIE'           => 'required',
            'form.foodFact.PROTEIN'           => 'required',
            'form.foodFact.FAT'               => 'required',
            'form.foodFact.CARBOHYDRATE'      => 'required',
            'form.foodFact.FIBER'             => 'required',
            'form.isPrimary'                  => 'required',
        ];
    }

    // ---------- Create/Update ----------
    public function updateOrCreate()
    {
        $this->validate();

        // تضمین حضور همهٔ کلیدهای فکت
        foreach (\Modules\Diet\Enum\FoodFactEnum::cases() as $foodFactEnum) {
            if (! array_key_exists($foodFactEnum->name, $this->form['foodFact'])) {
                $this->form['foodFact'][$foodFactEnum->name] = "-1";
            }
        }

        $payload = [
            'name'      => $this->form['name'],
            'food_fact' => $this->form['foodFact'],
            'type'      => $this->form['typeFood'] ? BasicFoodTypeEnum::COMBINED->value : BasicFoodTypeEnum::SIMPLE->value,
            'recipe'    => $this->form['recipe'] ?? null,
        ];

        if ($this->isEdited) {
            $this->basicFood->update($payload);
            $message = 'غذای پایه با موفقیت ویرایش شد';
        } else {
            $this->basicFood = BasicFood::create($payload);
            $message = 'غذای پایه با موفقیت اضافه شد';
        }

        // Units & Categories
        $this->basicFood->units()->detach();
        $this->basicFood->category()->detach();

        if (count($this->form['food_categories'])) {
            $cats = array_values(array_filter($this->form['food_categories']));
            if ($cats) $this->basicFood->category()->sync($cats);
        }

        if (count($this->form['food_unit'])) {
            foreach ($this->form['food_unit'] as $rowKey => $unitId) {
                if ($unitId == "") continue;
                $this->basicFood->units()->syncWithPivotValues([$unitId], [
                    'quantity_per_unit' => $this->form['quantity_per_unit'][$rowKey] ?? 0,
                    'max_allowed'       => $this->form['max_allowed'][$rowKey] ?? null,
                    'is_primary'        => $rowKey == $this->form['isPrimary'] ? 1 : 0,
                ], false);
            }
        }

        // Conditions
        if (isset($this->form['conditions'])) {
            foreach ($this->form['conditions'] as $conditionId => $valueCondition) {
                $filtered = array_filter($valueCondition, fn($v) => $v === true);
                $this->basicFood->conditions()->detach($conditionId);
                $this->basicFood->conditions()->attach($conditionId, [
                    'options' => json_encode(array_keys($filtered), JSON_UNESCAPED_UNICODE)
                ]);
            }
        }

        return redirect()->route('admin.basic_food.index')->with('success', $message);
    }

    // =========================================================
    //                       AI  Section
    // =========================================================

    private function refreshAiBalance(TokenService $tokens): void
    {
        try {
            $userId = auth()->id() ?: 0;
            $this->aiTokenBalance = (int) $tokens->balance($userId);
            $this->aiHasTokens    = $this->aiTokenBalance > 0;
        } catch (\Throwable $e) {
            $this->aiTokenBalance = 0;
            $this->aiHasTokens    = false;
        }
    }

    public function openAiModal(TokenService $tokens): void
    {
        if (empty($this->aiForm['food_name']) && !empty($this->form['name'])) {
            $this->aiForm['food_name'] = $this->form['name'];
        }

        // شمارش کش
        $index = \Cache::get('ai:basic:index', []);
        $this->cachedTotal = count($index);
        $this->showCache   = false;

        $this->refreshAiBalance($tokens);
        $this->dispatch('show-ai-modal');
    }

    public function showCacheList(): void
    {
        $this->showCache = true;
        $this->loadCachedIndex(1);
    }

    public function backToForm(): void
    {
        $this->showCache = false;
    }

    public function loadCachedIndex(int $page = 1): void
    {
        $this->cachedPage = max(1, (int) $page);
        $list = \Cache::get('ai:basic:index', []);
        // جدیدترین بالا
        usort($list, fn($a,$b) => (int)($b['ts'] ?? 0) <=> (int)($a['ts'] ?? 0));

        $this->cachedTotal = count($list);
        $offset = ($this->cachedPage - 1) * $this->cachedPerPage;
        $this->cachedItems = array_slice($list, $offset, $this->cachedPerPage);
    }

    public function useCachedAi(string $key): void
    {
        $raw = \Cache::get($key);
        if (!$raw || !is_array($raw)) return;

        $this->prefillFormFromAi($raw);
        $this->dispatch('hide-ai-modal');
    }


    public function submitAiPrefill(\Modules\AI\Services\AiMenuService $ai, \Modules\AI\Services\TokenService $tokens): void
    {
        // ... چک توکن و اعتبارسنجی نام غذا (form.name یا یک فیلد aiForm.food_name)
        $foodName = trim( $this->aiForm['food_name']);

        $raw = $ai->fetchRawBasicFood($foodName,$this->fetchData['foodCategories']->pluck('id' , 'title')->toArray() ,$this->fetchData['foodUnits']->pluck('id', 'name')->toArray() , $this->aiForceRefresh);
        if (!$raw) { /* هندل ارور/بستن مودال */ return; }

        // مهم: استخراج امن JSON
        $ai = $ai->extractJsonArrayFromResponse($raw);
        if (!is_array($ai)) return;

        // حالا map کن به فیلدهای فرم
        // مثال:
        $this->form['name'] = $ai['name'] ?? $this->form['name'] ?? '';
        $this->form['foodFact']['CALORIE']      = (float) ($ai['food_fact']['CALORIE'] ?? -1);
        $this->form['foodFact']['PROTEIN']      = (float) ($ai['food_fact']['PROTEIN'] ?? -1);
        $this->form['foodFact']['FAT']          = (float) ($ai['food_fact']['FAT'] ?? -1);
        $this->form['foodFact']['CARBOHYDRATE'] = (float) ($ai['food_fact']['CARBOHYDRATE'] ?? -1);
        $this->form['foodFact']['FIBER']        = (float) ($ai['food_fact']['FIBER'] ?? -1);

        // واحدها (اگر AI برگرداند):
        // $ai['units'] = [ ['unit_name'=>'قاشق غذاخوری','quantity_per_unit'=>20,'is_primary'=>1,'max_allowed'=>100], ... ]
        if (!empty($ai['units']) && is_array($ai['units'])) {
            $this->form['foodUnits'] = [];
            $this->form['food_unit'] = [];
            $this->form['quantity_per_unit'] = [];
            $this->form['max_allowed'] = [];
            $this->foodUnitCounter = 1;

            foreach ($ai['units'] as $u) {
                // پیدا کردن FoodUnit بر اساس نام
                $fu = \Modules\Diet\Entities\FoodUnit::where('name','LIKE','%'.($u['unit_name'] ?? '').'%')->first();
                if (!$fu) continue;

                $idx = $this->foodUnitCounter++;
                $this->form['foodUnits'][$idx] = $this->fetchData['foodUnits']; // لیست برای سلکت
                $this->form['food_unit'][$idx] = $fu->id;
                $this->form['quantity_per_unit'][$idx] = (float) ($u['quantity_per_unit'] ?? 0);
                $this->form['max_allowed'][$idx]       = $u['max_allowed'] ?? null;

                if (!empty($u['is_primary'])) {
                    $this->form['isPrimary'] = $idx;
                }
            }

            if (empty($this->form['isPrimary'])) {
                $this->form['isPrimary'] = 1; // پیش‌فرض
            }
        }

        // دسته‌ها اگر AI داد:
        // $ai['categories'] = ['غلات','حبوبات', ...]  ← باید به id نگاشت شود
        if (!empty($ai['categories']) && is_array($ai['categories'])) {
            $catIds = \Modules\Diet\Entities\FoodCategory::whereIn('title', $ai['categories'])->pluck('id')->toArray();
            if (!empty($catIds)) {
                $this->form['food_categories'] = array_values($catIds);
                $this->foodCategoryCounter = count($catIds) - 1;
            }
        }

        $this->dispatch('hide-ai-modal');
    }
    private function prefillFormFromAi(array $raw): void
    {
        // RAW -> output[message]->content[output_text] (مانند سرویس قبلی‌ات)
        if (!isset($raw['output'][1]['content'][0]['text'])) return;
        $json = json_decode($raw['output'][1]['content'][0]['text'], true);
        if (!is_array($json)) return;

        // --- 1) Name / Recipe ---
        if (!empty($json['name']))   $this->form['name']   = trim($json['name']);
        if (!empty($json['recipe'])) $this->form['recipe'] = (string) $json['recipe'];

        // --- 2) Food Facts (100g) ---
        $expectedFacts = ['CALORIE','PROTEIN','FAT','CARBOHYDRATE','FIBER'];
        foreach ($expectedFacts as $key) {
            if (isset($json['foodFact'][$key])) {
                $this->form['foodFact'][$key] = $json['foodFact'][$key];
            }
        }

        // تضمین کلیدها
        foreach (\Modules\Diet\Enum\FoodFactEnum::cases() as $enum) {
            if (! array_key_exists($enum->name, $this->form['foodFact'])) {
                $this->form['foodFact'][$enum->name] = "-1";
            }
        }

        // --- 3) Units ---
        // انتظار: units = [{unit: "قاشق غذاخوری", grams: 20, is_primary: true|false, max_allowed: 20}, ...]
        $this->form['food_unit']         = [];
        $this->form['quantity_per_unit'] = [];
        $this->form['max_allowed']       = [];
        $this->form['isPrimary']         = null;

        // ردیف اول اگر چیزی نبود
        if (empty($this->form['foodUnits'])) {
            $this->foodUnitCounter = 1;
            $this->form['foodUnits'][$this->foodUnitCounter] = $this->fetchData['foodUnits'];
        }

        $row = 1;
        if (!empty($json['units']) && is_array($json['units'])) {
            // پاک‌سازی همهٔ ردیف‌های فعلی و شروع از 1
            $this->form['foodUnits'] = [];
            $this->foodUnitCounter   = 0;

            foreach ($json['units'] as $u) {
                $unitName = trim((string)($u['unit'] ?? ''));
                $grams    = (float)($u['grams'] ?? 0);
                $max      = isset($u['max_allowed']) && $u['max_allowed'] !== '' ? (int)$u['max_allowed'] : null;
                $isPrim   = (bool)($u['is_primary'] ?? false);

                if ($unitName === '' || $grams <= 0) continue;

                // Match به FoodUnit
                $unitId = $this->matchUnitIdByName($unitName);
                // اگر پیدا نشد، اولین واحد موجود را بگذار
                if (!$unitId && $this->fetchData['foodUnits']->count()) {
                    $unitId = $this->fetchData['foodUnits']->first()->id;
                }

                ++$this->foodUnitCounter;
                $rowKey = $this->foodUnitCounter;

                $this->form['foodUnits'][$rowKey]          = $this->fetchData['foodUnits'];
                $this->form['food_unit'][$rowKey]          = $unitId;
                $this->form['quantity_per_unit'][$rowKey]  = $grams;
                $this->form['max_allowed'][$rowKey]        = $max;

                if ($isPrim && $this->form['isPrimary'] === null) {
                    $this->form['isPrimary'] = $rowKey;
                }
            }
        }

        // اگر primary تعیین نشد ولی ردیفی هست، اولی را primary بگذار
        if ($this->form['isPrimary'] === null && !empty($this->form['food_unit'])) {
            $firstRowKey = array_key_first($this->form['food_unit']);
            $this->form['isPrimary'] = $firstRowKey;
        }

        // --- 4) Categories (mapping by title) ---
        // انتظار: categories_titles = ["غلات", "لبنیات", ...]
        if (!empty($json['categories_titles']) && is_array($json['categories_titles'])) {
            $this->form['food_categories'] = [];
            $i = 0;
            foreach ($json['categories_titles'] as $title) {
                $id = $this->matchCategoryIdByTitle((string)$title);
                if ($id) {
                    $this->form['food_categories'][$i++] = $id;
                }
            }
            $this->foodCategoryCounter = max(0, count($this->form['food_categories']) - 1);
            if ($i === 0) { $this->form['food_categories'][0] = ''; }
        }

        // --- 5) Conditions ---
        // ساختار پیشنهادی AI:
        //   diseases: {"فشار خون بالا":1, "دیابت نوع ۲":1, ...}
        //   dietaryRestrictions: {realKey|index => 0/1 ...}  (بر اساس ConditionKeyEnum::FOOD_RESTRICTION)
        //   foodAllergies:       {realKey|index => 0/1 ...}  (بر اساس ConditionKeyEnum::FOOD_ALLERGY)
        //   dietPatterns:        {realKey|index => 0/1 ...}  (بر اساس ConditionKeyEnum::DIET_PATTERN)
        $aiDiseases = (array)($json['diseases'] ?? []);
        $aiFR       = (array)($json['dietaryRestrictions'] ?? []);
        $aiFA       = (array)($json['foodAllergies'] ?? []);
        $aiDP       = (array)($json['dietPatterns'] ?? []);

        foreach ($this->fetchData['conditions'] as $condition) {
            $condId = $condition->id;
            $enum   = $condition->key->value;

            if ($enum === ConditionKeyEnum::DISEASE->value) {
                foreach ($this->fetchData['disease'] as $dModel) {
                    $ok = null;
                    // بر اساس نام (Persian) اگر آمده باشد:
                    if (array_key_exists($dModel->name, $aiDiseases)) {
                        $ok = (int)$aiDiseases[$dModel->name] === 1;
                    }
                    // اگر نیامده بود، رها کن (یا از پیش‌فرض‌ها استفاده کن)
                    if ($ok === true) {
                        $this->form['conditions'][$condId][$dModel->id] = true;
                    }
                }
                continue;
            }

            // سایر کانفیگ‌ها: keys واقعی در options['items']['keys']
            $keys = $condition->options['items']['keys'] ?? [];
            foreach ($keys as $realKey) {
                $flag = null;
                if ($enum === ConditionKeyEnum::FOOD_RESTRICTION->value) {
                    $flag = $aiFR[$realKey] ?? null;
                } elseif ($enum === ConditionKeyEnum::FOOD_ALLERGY->value) {
                    $flag = $aiFA[$realKey] ?? null;
                } elseif ($enum === ConditionKeyEnum::DIET_PATTERN->value) {
                    $flag = $aiDP[$realKey] ?? null;
                }
                if ($flag === 1 || $flag === true || $flag === '1') {
                    $this->form['conditions'][$condId][$realKey] = true;
                }
            }
        }
    }

    private function matchUnitIdByName(string $unitName): ?int
    {
        $unitName = trim($unitName);
        if ($unitName === '') return null;
        // match دقیق
        $m = $this->fetchData['foodUnits']->firstWhere('name', $unitName);
        if ($m) return $m->id;

        // match تقریبی (حروف بدون فاصله/نیم‌فاصله)
        $clean = static fn($s) => str_replace(['‌',' '], '', mb_strtolower(trim($s)));
        $needle = $clean($unitName);
        foreach ($this->fetchData['foodUnits'] as $fu) {
            if ($clean($fu->name) === $needle) return $fu->id;
        }
        foreach ($this->fetchData['foodUnits'] as $fu) {
            if (Str::contains($clean($fu->name), $needle) || Str::contains($needle, $clean($fu->name))) {
                return $fu->id;
            }
        }
        return null;
    }

    private function matchCategoryIdByTitle(string $title): ?int
    {
        $title = trim($title);
        if ($title === '') return null;

        $m = $this->fetchData['foodCategories']->firstWhere('title', $title);
        if ($m) return $m->id;

        $clean = static fn($s) => str_replace(['‌',' '], '', mb_strtolower(trim($s)));
        $needle = $clean($title);
        foreach ($this->fetchData['foodCategories'] as $fc) {
            if ($clean($fc->title) === $needle) return $fc->id;
        }
        foreach ($this->fetchData['foodCategories'] as $fc) {
            if (Str::contains($clean($fc->title), $needle) || Str::contains($needle, $clean($fc->title))) {
                return $fc->id;
            }
        }
        return null;
    }
}
