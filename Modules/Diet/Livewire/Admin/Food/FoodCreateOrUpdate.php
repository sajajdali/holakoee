<?php

namespace Modules\Diet\Livewire\Admin\Food;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AI\Services\AiMenuService;
use Modules\Core\Entities\Disease;
use Modules\Diet\Entities\BasicFood;
use Modules\Diet\Entities\Condition;
use Modules\Diet\Entities\Food;
use Modules\Diet\Entities\Meal;
use Modules\Diet\Enum\ConditionApplyItemEnum;
use Modules\Diet\Enum\ConditionKeyEnum;
use Modules\Diet\Enum\FoodTypeEnum;
use Modules\Diet\Livewire\Admin\Traits\ConditionTrait;
use Illuminate\Support\Facades\Auth;
use Modules\AI\Services\TokenService;
class FoodCreateOrUpdate extends Component
{
    use AuthorizesRequests, ConditionTrait;

    // fetch data
    public ?Food $food = null;

    public array $fetchData = [
        'max' => 20,
        'parent_id' => null
    ];


    public array $form = [
        'meals' => [],
        'basic_foods' => [],
        'conditions' => [],
        'countBasicFoods' => 1,
        'BasicFoodUnits' => null,
        'interval_step' => null,
    ];

    public $computData = [
        'carb' => 0,
        'protein' => 0,
        'fat' => 0,
        'fiber' => 0,
        'recipe' => null,
        'min_calorie' => 0,
        'max_calorie' => null,
    ];
    public $isEdited = false;


    protected $rules = [
        'form.name' => 'required|string',
        'form.meals' => 'required|array',
        //        'form.main' => 'required',
    ];

    protected $messages = [
        'form.name.required'  => 'نام غذا را وارد کنید',
        'form.main.required'  => 'یک وعده را به عنوان وعده اصلی باید انتخاب کنید',
        'form.meals.required' => 'وعده‌هایی که این غذا میتواند در آن تجویز شود انتخاب نشده',

        // پیام‌های سفارشی برای AI modal
        'aiForm.food_name.required' => 'فیلد نام غذا الزامی است',
        'aiForm.foods.required'     => 'فیلد مواد/غذاها الزامی است',
        'aiForm.food_name.min'      => 'نام غذا باید حداقل ۲ کاراکتر باشد',
        'aiForm.foods.min'          => 'لیست مواد/غذاها باید حداقل ۲ کاراکتر داشته باشد',
    ];
    public array $aiForm = [
        'food_name' => '',
        'foods'     => '', // متن: با ویرگول/ویرگول فارسی/اینتر جدا می‌کنی
    ];
    public bool $aiHasTokens = true;
    public int  $aiTokenBalance = 0;
    public bool $aiForceRefresh = false; // ⇦ نادیده گرفتن کش


    // only load ai cash
    public array $cachedItems = [];  // [{key,title,ts}]
    public int   $cachedPage  = 1;
    public int   $cachedPerPage = 10;
    public int   $cachedTotal = 0;

    public function loadCachedIndex(int $page = 1): void
    {
        $this->cachedPage = max(1, (int) $page);

        $list = \Cache::get('ai:menu:index', []); // ایندکسی که در سرویس ساختیم
        // مرتب‌سازی نزولی بر اساس ts
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
    // only load ai cash


    private function refreshAiBalance(TokenService $tokens): void
    {
        try {
            $userId = Auth::id() ?: 0;
            $this->aiTokenBalance = (int) $tokens->balance($userId); // فرض: متد balance موجوده
            $this->aiHasTokens    = $this->aiTokenBalance > 0;
        } catch (\Throwable $e) {
            // اگر سرویسی در دسترس نبود، مودال را باز کنیم ولی اجرا را ببندیم
            $this->aiTokenBalance = 0;
            $this->aiHasTokens    = false;
            // Log::warning('Token balance check failed', ['e' => $e->getMessage()]);
        }
    }

    public bool $showCache = false;

    public function openAiModal(TokenService $tokens): void
    {
        if (empty($this->aiForm['food_name']) && !empty($this->form['name'])) {
            $this->aiForm['food_name'] = $this->form['name'];
        }

//        $this->loadCachedIndex();

        // get count cash
        $list = \Cache::get('ai:menu:index', []); // ایندکسی که در سرویس ساختیم
        $this->cachedTotal = count($list);
        $this->showCache = false;
        $this->refreshAiBalance($tokens); // ← موجودی را به‌روز کن
        $this->dispatch('show-ai-modal');
    }
    public function showCacheList(): void
    {
        $this->showCache = true;
        $this->loadCachedIndex(1); // فقط وقتی کاربر خواست، لود کن
    }
    public function backToForm(): void
    {
        $this->showCache = false;
    }

    public function closeAiModal(): void
    {
        $this->dispatch('hide-ai-modal');
        $this->aiForceRefresh = false;

    }


    public function submitAiPrefill(AiMenuService $ai, TokenService $tokens): void
    {
        if ($this->isEdited) return;

        $this->refreshAiBalance($tokens);
        if (!$this->aiHasTokens) {
            $this->addError('aiForm.tokens', 'شما توکن ندارید. لطفاً ابتدا توکن تهیه کنید.');
            return;
        }

        $this->validate([
            'aiForm.food_name' => 'required|string|min:2|max:255',
            'aiForm.foods'     => 'required|string|min:2|max:3000',
        ]);

        $foods = preg_split('/[\n,،]+/u', $this->aiForm['foods']);
        $foods = array_values(array_filter(array_map(fn($s) => trim($s), $foods), fn($s) => $s !== ''));

        if (count($foods) === 0) {
            $this->dispatch('hide-ai-modal');
            return;
        }

        $foodName = trim($this->aiForm['food_name']);

        // ⇦ اگر aiForceRefresh=true باشد، کش فراموش می‌شود و درخواست جدید حتماً به سرور می‌رود
        $data = $ai->fetchRaw($foods, $foodName, $this->aiForceRefresh);
        if (!$data) {
            $this->dispatch('hide-ai-modal');
            return;
        }

        $this->prefillFormFromAi($data);
        $this->dispatch('hide-ai-modal');

        $this->aiForceRefresh = false;
    }
    public function aiPrefill(AiMenuService $ai): void
    {
        if ($this->isEdited) return;

        $foods = ['ماهیچه','باقالی پلو'];
        $foodName = 'باقالی پلو با ماهیچه';

        $data = $ai->fetchRaw($foods, $foodName);
        if (!$data) return;

        $this->prefillFormFromAi($data); // همان متدی که پیش‌تر فرستادم
    }
    private function prefillFormFromAi(array $raw): void
    {
        // 1) دیکود خروجی مدل
        if (!isset($raw['output'][1]['content'][0]['text'][0])) {
            return;
        }
        $ai = json_decode($raw['output'][1]['content'][0]['text'], true);
        if (!is_array($ai)) return;
        if (count($ai) == 1) {
            $ai = $ai[0];
        }

        // 2) foods/items
        $this->form['foods'] = [];
        foreach (($ai['items'] ?? []) as $i => $item) {
            $basicFood = \Modules\Diet\Entities\BasicFood::find($item['id'] ?? 0)
                ?? \Modules\Diet\Entities\BasicFood::where('name','LIKE','%'.($item['name'] ?? '').'%')->first();

            if (!$basicFood) continue;

            $unitName = $basicFood->units()->where('is_primary', 1)->first()?->name
                ?? $basicFood->units()->first()?->name;

            $this->form['foods'][$i] = [
                'basic_food_id' => $basicFood->id,
                'unit_name'     => $unitName,
                'unit'          => $item['min'] ?? 1,
                'max'           => $item['max'] ?? null,
            ];
        }
        $this->form['countBasicFoods'] = count($this->form['foods']);

        // 3) meals
        $this->form['meals'] = [];
        $mealsFlags = $ai['meals'] ?? [];
        foreach ($this->fetchData['mealList'] as $i => $mealModel) {
            if (!empty($mealsFlags[$i])) {
                $this->form['meals'][] = $mealModel->id;
            }
        }

        // 4) conditions
        foreach ($this->fetchData['conditions'] as $condition) {
            $enumVal = $condition->key->value;

            if ($enumVal === \Modules\Diet\Enum\ConditionKeyEnum::DISEASE->value) {
                // diseases: بر اساس ترتیب $fetchData['disease']
                $diseaseFlags = $ai['diseases'] ?? [];
                foreach ($this->fetchData['disease'] as $i => $diseaseModel) {
                    if (!empty($diseaseFlags[$i])) {
                        $this->form['conditions'][$condition->id][$diseaseModel->id] = true;
                    }
                }
                continue;
            }

            // map به فیلدهای AI
            $map = [
                \Modules\Diet\Enum\ConditionKeyEnum::FOOD_RESTRICTION->value => 'dietaryRestrictions',
                \Modules\Diet\Enum\ConditionKeyEnum::FOOD_ALLERGY->value     => 'foodAllergies',
                \Modules\Diet\Enum\ConditionKeyEnum::DIET_PATTERN->value     => 'categories',
            ];
            $field = $map[$enumVal] ?? null;
            if (!$field) continue;

            $flags = $ai[$field] ?? [];
            $keys  = $condition->options['items']['keys'] ?? [];
            foreach ($keys as $i => $realKey) {
                if (!empty($flags[$i])) {
                    $this->form['conditions'][$condition->id][$realKey] = true;
                }
            }
        }

        // 5) نام غذا
        if (!empty($ai['menu_name'])) {
            $this->form['name'] = $ai['menu_name'];
        }

        $this->calculateData();
        $this->dispatch('updateUi');
    }
    public function mount(Food $food)
    {
        $this->fetchData['mealList'] = Meal::all();
        $this->fetchData['disease'] = Disease::active()->get();
        $this->fetchData['BasicFoods'] = BasicFood::active()->whereHas('units')->get();
        $this->fetchData['conditions'] = Condition::whereJsonContains('apply_to', ConditionApplyItemEnum::FOOD->value)->get();
        // auto checked fields
        foreach ($this->fetchData['conditions'] as $value) {

            // remove all disease in list checked
            if ($value->key == ConditionKeyEnum::DISEASE) {
                continue;
            }
            // remove all disease in list checked

            foreach ($value->options['checked'] as $k => $check) {
                $this->form['conditions'][$value->id][$value->options['items']['keys'][$k]] = $value->options['checked'][$k];
            }
        }
        // auto checked fields

        $food = request()->route('food');
        if ($food instanceof Food) {
            //            $this->authorize('update', $food);
            $this->isEdited = true;
            $this->food = $food;
            $this->form['name'] = $this->food->name ?? '';
            $this->form['calories'] = $this->food->calories;
            $this->form['carb'] = $this->food->carb;
            $this->form['fat'] = $this->food->fat;
            $this->form['protein'] = $this->food->protein;
            $this->form['fiber'] = $this->food->fiber;
            $this->form['recipe'] = $this->food->recipe;
            $this->form['interval_step'] = $this->food->detail['interval_step'] ?? null;
            $this->fetchData['parent_id'] = $this->food->parent_id;

            foreach ($this->food->conditions as $condition) {
                $this->form['conditions'][$condition->id] = $this->convertToAssociativeArray(json_decode($condition->pivot->options));
            }

            $this->form['meals'] = $this->food->meals()->pluck('id')->toArray() ?? [];

            // list basic foods
            $basicFoodList = $this->food->basicFoodPivot()->orderBy('basic_food_pivot.id')->get();
            if (count($basicFoodList)) {
                $this->form['countBasicFoods'] = count($basicFoodList);
                $i = 0;
                foreach ($basicFoodList as $basicFood) {
                    $this->form['foods'][$i]['basic_food_id'] = $basicFood->id;
                    $this->form['foods'][$i]['unit_name'] = $basicFood->units()->where('is_primary', '1')->first()?->name ?? $basicFood->units()->first()->name;
                    $this->form['foods'][$i]['unit'] = $basicFood->pivot->quantity;
                    $this->form['foods'][$i]['max'] = $basicFood->pivot->maximum;
                    $this->form['foods'][$i]['extend'] = $basicFood->pivot->extend == 1;
                    $i++;
                }
                $this->calculateData();
            }
            // list basic foods
        }
        $this->dispatch('updateUi');
    }


    #[Computed]
    public function calculateData()
    {
        $this->computData = [
            'calorie' => 0,
            'carb' => 0,
            'protein' => 0,
            'fat' => 0,
            'fiber' => 0,
            'recipe' => null,
            'min_calorie' => 0,
            'maximum_amount_of_food' => null,
        ];
        $smallerFoodMax = $this->getSmallestBasicFoodId();
        foreach ($this->form['foods'] as $food) {
            $basicFood = BasicFood::find($food['basic_food_id']);
            $foocFactsEnums = \Modules\Diet\Enum\FoodFactEnum::cases();
            foreach ($foocFactsEnums as $foodFactEnum) {
                $model = [];
                if (!in_array($foodFactEnum->value, $basicFood->food_fact)) {
                    $model[$foodFactEnum->value] = "-1";
                }
                if (!empty($model)) {
                    $priviusFoodFact = $basicFood->food_fact;
                    $basicFood->update(['food_fact' => array_merge($priviusFoodFact, $model)]);
                }
            }
            $foodUnit = $basicFood->units()->where('is_primary', true)->first() ?? $basicFood->units()->first();
            $quantity_per_unit = $foodUnit->pivot->quantity_per_unit;
            $unit = $food['unit'] ?? 1;
            $caloriePerUnit = (($quantity_per_unit * $unit * $basicFood->food_fact['CALORIE']) / 100);
            $proteinPerUnit = (($quantity_per_unit * $unit * $basicFood->food_fact['PROTEIN']) / 100);
            $carbPerUnit = (($quantity_per_unit * $unit * $basicFood->food_fact['CARBOHYDRATE']) / 100);
            $fiberPerUnit = (($quantity_per_unit * $unit * $basicFood->food_fact['FIBER']) / 100);
            $fatPerUnit = (($quantity_per_unit * $unit * $basicFood->food_fact['FAT']) / 100);
            $maxAmount = $food['max'] ?? null;
            if ($maxAmount == null && $smallerFoodMax !== false && $smallerFoodMax['smallestBasicFoodId'] == $basicFood->id) {
                $this->fetchData['minSelected'] = $basicFood->name;
                $maxAmount = $this->fetchData['max'];
            }
            $this->computData['min_calorie'] = round($this->computData['min_calorie'] += ($caloriePerUnit), 2);
            $this->computData['carb'] = round($this->computData['carb'] += $carbPerUnit, 2);
            $this->computData['protein'] = round($this->computData['protein'] += $proteinPerUnit, 2);
            $this->computData['fiber'] = round($this->computData['fiber'] += $fiberPerUnit, 2);
            $this->computData['fat'] = round($this->computData['fat'] += $fatPerUnit, 2);
            $this->computData['recipe_list'][] = '';
            if ($maxAmount) {
                $unit = max(1, (int) $unit);
                $maxPerThisUnit = max(1, floor($maxAmount / $unit));
                if (!isset($this->computData['maximum_amount_of_food']) || $maxPerThisUnit < $this->computData['maximum_amount_of_food']) {
                    $this->computData['maximum_amount_of_food'] = $maxPerThisUnit;
                }
            }
        }
        if (isset($this->computData['recipe_list'])) {
            $this->computData['recipe'] = implode("<hr>", array_filter($this->computData['recipe_list']));
        }
    }

    // Confirming the maximum value for an item whose value is lower than the others
    public function getSmallestBasicFoodId(): false|array
    {
        $allMaxNull = false;
        $smallestUnit = null;
        $smallestBasicFoodId = null;

        if (!isset($this->form['foods'])) {
            return false;
        }
        foreach ($this->form['foods'] as $item) {
            if (isset($item['max'])) {
                $allMaxNull = true;
            }

            if ($smallestUnit === null || $item['unit'] < $smallestUnit) {
                $smallestUnit = $item['unit'];
                $smallestBasicFoodId = $item['basic_food_id'];
            }
        }

        if ($allMaxNull) {
            return false;
        }

        return [
            'smallestUnit' => $smallestUnit,
            'smallestBasicFoodId' => $smallestBasicFoodId
        ];
    }

    public function addBasicFoodItem(): void
    {
        $this->form['countBasicFoods'] += 1;
        $this->dispatch('updateUi');
    }

    public function removeBasicFoodItem($itemSelected): void
    {
        $this->form['countBasicFoods'] -= 1;
        if (isset($this->form['main']) && $this->form['main'] == $this->form['foods'][$itemSelected]['basic_food_id']) {
            unset($this->form['main']);
        }
        unset($this->form['foods'][$itemSelected]);
        if (isset($this->form['foods'])) {
            $this->form['foods'] = array_values($this->form['foods']);
            $this->calculateData();
        }
        $this->dispatch('updateUi');
    }

    #[On('addBasicFood')]
    public function addBasicFood($basicFoodId = 0, $foodNumber = 0)
    {

        $baseFood = BasicFood::find($basicFoodId);
        $this->form['foods'][$foodNumber]['unit_name'] = $baseFood->units()->where('is_primary', '1')->first()?->name ?? $baseFood->units()->first()->name;
        $this->form['foods'][$foodNumber]['basic_food_id'] = $basicFoodId;
        // auto select first unit
        $this->form['foods'][$foodNumber]['unit'] = 1;
        $this->form['foods'][$foodNumber]['max'] = '';
        // auto select first unit
        $this->calculateData();
        $this->dispatch('updateUi');
    }

    public function updated($name, $value): void
    {


        //when change unit base food
        if (preg_match('/\b(unit|max)\b/', $name)) {
            $this->calculateData();
        }

        //when change unit base food

        // when updated main nutrition and this food is complex
        $this->dispatch('updateUi');
        // when updated main nutrition and this food is complex
    }

    public function updateOrCreate()
    {
        $this->validate();

        $modelCreateOrUpdate = $this->prepareModelData($this->form['name'], $this->computData);
        if ($this->isEdited) {
            $this->food->update($modelCreateOrUpdate);
            $message = 'وعده با موفقیت ویرایش شد';
        } else {

            $this->food = Food::create($modelCreateOrUpdate);
            $message = 'وعده با موفقیت اضافه شد';
        }
        $this->syncConditions($this->food, $this->form['conditions']);
        $this->syncBasicFoods($this->food, $this->form['foods']);
        $this->syncMeals($this->food, $this->form['meals']);

        // Creating a foods list if it is not the parent
        if ($this->food->parent_id == null){
            $this->createSampleDiets();
        }

//        return redirect()->route('admin.food.index')->with('success', $message);
        if (!isset($this->fetchData['parent_id']) && isset($this->food->parent_id)){
            return redirect()->route('admin.food.index' , ['parent_id' => $this->food->parent_id])->with('success', $message);

        }
        return redirect()->route('admin.food.index')->with('success', $message);
    }

    private function prepareModelData($name, $computData)
    {
        return [
            'name' => $name,
            'carb' => $computData['carb'] ?? 0,
            'max_carb' => isset($computData['carb']) ? $this->calculateMaxAmount($computData['carb']) : 0,
            'protein' => $computData['protein'] ?? 0,
            'max_protein' => isset($computData['protein']) ? $this->calculateMaxAmount($computData['protein']) : 0,
            'calories' => $computData['min_calorie'] ?? 0,
            'max_calories' => isset($computData['min_calorie']) ? $this->calculateMaxAmount($computData['min_calorie']) : 0,
            'fat' => $computData['fat'] ?? 0,
            'max_fat' => isset($computData['fat']) ? $this->calculateMaxAmount($computData['fat']) : 0,
            'fiber' => $computData['fiber'] ?? 0,
            'max_fiber' => isset($computData['fiber']) ? $this->calculateMaxAmount($computData['fiber']) : 0,
            'recipe' => $computData['recipe'] ?? '',
            'max_per_unit' => $computData['maximum_amount_of_food'],
            'type' => FoodTypeEnum::COMBINED->value,
            'detail' => [
                'interval_step' => $this->form['interval_step']
            ],
        ];
    }

    private function calculateMaxAmount($foodFact)
    {
        return $foodFact * $this->computData['maximum_amount_of_food'];
    }

    private function syncConditions($food, $conditions)
    {
        foreach ($conditions as $keyCondition => $valueCondition) {
            $filteredArray = array_filter($valueCondition, function ($value) {
                return $value === true;
            });
            $food->conditions()->detach($keyCondition);
            $food->conditions()->attach($keyCondition, ['options' => json_encode(array_keys($filteredArray), JSON_UNESCAPED_UNICODE)]);
        }
    }

    private function syncBasicFoods($food, $basicFoodList)
    {
        $food->basicFoodPivot()->detach();
        if (isset($basicFoodList)) {
            foreach ($basicFoodList as $basicFood) {
                $max = isset($basicFood['max']) && $basicFood['max'] !== '' ? $basicFood['max'] : null;
                if ($this->getSmallestBasicFoodId() !== false && $this->getSmallestBasicFoodId()['smallestBasicFoodId'] == $basicFood['basic_food_id']) {
                    $max = $this->fetchData['max'];
                }
                $food->basicFoodPivot()->syncWithPivotValues([$basicFood['basic_food_id']], [
                    'quantity' => ($basicFood['unit'] == "" || $basicFood['unit'] == null) ? 1 : $basicFood['unit'],
                    'maximum' => $max,
                    'extend' => isset($basicFood['extend']) && $basicFood['extend'] ? 1 : 0,
                ], false);
            }
        }
    }

    private function syncMeals($food, $meals)
    {
        $food->meals()->sync($meals);
    }
    private function syncBasicFoodsOptimized(Food $food, array $basicFoodList, $smallestInfo = false): void
    {
        $food->basicFoodPivot()->detach();

        if (!isset($basicFoodList)) {
            return;
        }

        foreach ($basicFoodList as $basicFood) {
            $max = isset($basicFood['max']) && $basicFood['max'] !== '' ? $basicFood['max'] : null;

            // اگر اون منطق «اونی که واحدش کمترینه، maxش رو بذار روی fetchData['max']» فعال باشه
            if ($smallestInfo !== false && $smallestInfo['smallestBasicFoodId'] == $basicFood['basic_food_id']) {
                $max = $this->fetchData['max'];
            }

            $food->basicFoodPivot()->syncWithPivotValues(
                [$basicFood['basic_food_id']],
                [
                    'quantity' => ($basicFood['unit'] == "" || $basicFood['unit'] == null) ? 1 : $basicFood['unit'],
                    'maximum'  => $max,
                    'extend'   => isset($basicFood['extend']) && $basicFood['extend'] ? 1 : 0,
                ],
                false
            );
        }
    }
    public function saveFoodVariants(array $combinations, $parentId): void
    {
        DB::transaction(function () use ($combinations, $parentId) {

            $maxCalorie = 0;

            // این سه تا توی کل حلقه ثابت‌اند، لازم نیست هر بار از روی this بخونیم
            $baseConditions = $this->form['conditions'] ?? [];
            $baseMeals      = $this->form['meals'] ?? [];
            $baseName       = $this->form['name'] ?? '';

            foreach ($combinations as $key => $combination) {
                // اگر واقعا می‌خواستی اولین مورد رو اسکیپ کنی، اینو از کامنت دربیار
                // if ($key === 0) {
                //     continue;
                // }

                // برای هر ترکیب فقط همون foods عوض می‌شه
                $this->form['foods'] = $combination;

                // محاسبه‌ی تغذیه‌ای از روی foods جدید
                $this->calculateData();

                // مدل غذای جدید
                $modelCreateOrUpdate = $this->prepareModelData(
                // اگر جایی توی فرم موقع حلقه اسم رو عوض کرده بودی، همونو بگیر
                    $this->form['name'] ?? $baseName,
                    $this->computData
                );
                $modelCreateOrUpdate['parent_id'] = $parentId;

                // ساخت غذا
                $this->food = Food::create($modelCreateOrUpdate);

                // چون شرط‌ها توی این تابع تغییر نمی‌کنن، همون قبلی رو سینک می‌کنیم
                $this->syncConditions($this->food, $baseConditions);

                // برای این‌که توی syncBasicFoods دوباره getSmallestBasicFoodId() صدا نشه،
                // همون‌جا از قبل حساب می‌کنیم و بهش می‌فرستیم
                $smallestInfo = $this->getSmallestBasicFoodId();
                $this->syncBasicFoodsOptimized($this->food, $this->form['foods'], $smallestInfo);

                // وعده‌ها هم ثابت هستند
                $this->syncMeals($this->food, $baseMeals);

                // نگه داشتن بیشترین کالری
                if ($this->food->calories > $maxCalorie) {
                    $maxCalorie = $this->food->calories;
                }
            }

            // آپدیت غذای والد با بیشترین کالری
            if ($this->food->parent !== null) {
                $parentFood = $this->food->parent;
                if ($parentFood->max_calories < $maxCalorie) {
                    $parentFood->max_calories = round($maxCalorie);
                    $parentFood->save();
                }
            }
        });
    }

    public function createSampleDiets()
    {
        $interval_step = isset($this->form['interval_step']) && $this->form['interval_step'] !== "" ? $this->form['interval_step'] : null;
//        $allSampleDiets = app('dietService')->generateFoodVariantsList($this->food , $interval_step);
//        $allSampleDiets = app('dietService')->generateFoodCombinations($this->food , $interval_step);
        $this->food->children()->delete();
        $allSampleDiets = $this->generateCombinationsWithStepLimit($this->food , $interval_step);
        $this->saveFoodVariants($allSampleDiets, $this->food->id);

    }

    private function generateCombinationsWithStepLimit(Food $food, $intervalStep)
    {
        $allSampleDiets = app('dietService')->generateFoodCombinations($food, $intervalStep);

        if (count($allSampleDiets) > 1000) {
            if ($intervalStep === null) {
                $intervalStep = 5;
            } elseif ($intervalStep > 1) {
                $intervalStep--;
            } else {
                return $allSampleDiets;
            }
            return $this->generateCombinationsWithStepLimit($food, $intervalStep);
        }
        $this->saveFinalIntervalStep($food, $intervalStep);
        return $allSampleDiets;
    }
    private function saveFinalIntervalStep(Food $food, $intervalStep)
    {
        $detail = $food->detail;
        $detail['interval_step'] = $intervalStep;

        $this->food->update([
            'detail' => $detail,
        ]);

    }

    private function computeSampleData($listBody)
    {

        return [
            'carb' => $this->sumNutrient($listBody, 'carb'),
            'protein' => $this->sumNutrient($listBody, 'protein'),
            'min_calorie' => $this->sumNutrient($listBody, 'calorie'),
            'fat' => $this->sumNutrient($listBody, 'fat'),
            'fiber' => $this->sumNutrient($listBody, 'fiber'),
            'recipe' => $this->computData['recipe'] ?? '',
            'maximum_amount_of_food' => $this->computData['maximum_amount_of_food'],
        ];
    }

    private function sumNutrient(array $foods, string $nutrient): float
    {
        $total = 0.0;

        foreach ($foods as $food) {
            if (isset($food['calculateFact'][$nutrient])) {
                $total += $food['calculateFact'][$nutrient];
            }
        }

        return $total;
    }

    private function syncBasicFoodPivot($food, $body)
    {
        $max = isset($body['max']) && $body['max'] !== '' ? $body['max'] : null;
        if ($this->getSmallestBasicFoodId() !== false && $this->getSmallestBasicFoodId()['smallestBasicFoodId'] == $body['food_id']) {
            $max = $this->fetchData['max'];
        }
        $food->basicFoodPivot()->syncWithPivotValues([$body['food_id']], [
            'quantity' => ($body['amount'] == "" || $body['amount'] == null) ? 1 : $body['amount'],
            'maximum' => $max,
            'extend' => 0,
        ], false);
    }

    public function render()
    {
        $title = (!is_null($this->food) && $this->food->exists) ? 'ویرایش غذا' : 'ایجاد غذای جدید';

        return view('diet::livewire.admin.food.food-create-or-update')->title($title);
    }
}
