<?php

namespace Modules\Diet\Livewire\Admin\Recipe;

use App\trait\UploadFileTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Modules\Diet\Entities\Food;
use Modules\Diet\Entities\FoodUnit;
use Modules\Diet\Entities\BasicFood;
use Modules\Recipe\app\Models\Recipe;
use Modules\Recipe\app\Models\RecipeCategory;

class RecipeUpdateOrCreate extends Component
{
    use WithFileUploads , UploadFileTrait;
    protected $filePath = 'recipe';

    public ?Recipe $recipe;
    public array $fetchData = [];
    public array $form = [
        'instructionsCounter' => 1,
        'countBasicFoods' => 1,
        'active' => true,
    ];
    public $isEdited = false;
    public $computData = [
        'carb' => 0,
        'protein' => 0,
        'fat' => 0,
        'fiber' => 0,
        'recipe' => null,
        'min_calorie' => 0,
        'max_calorie' => null,
    ];

    protected function messages()
    {
        return [
            'form.name.required' => 'فیلد نام غذا اجباری است',
            'form.name.max' => 'کاراکتر های وارده بیش از حد مجاز است',
            'form.time.required' => 'فیلد زمان اجباری است',
            'form.serving.required' => 'فیلد برای چند نفر اجباری است',
            'form.weight.required' => 'فیلد وزن غذا اجباری است',
            'form.calorie.required' => 'فیلد کالری اجباری است',
            'form.difficulty.required' => 'فیلد درجه سختی اجباری است',
            'form.category.required' => 'فیلد دسته بندی اجباری است',
            'form.recipe.unit.required' => 'لطفا واحد غذا را انتخاب کنید',
            'form.recipe.gram.required' => 'لطفا مقدار گرم در هر واحد غذایی را وارد کنید',
            'form.instroduction.0.required' => 'وارد کردن دستور پخت الزامی میباشد',
            'form.instroduction.*.required' => 'در صورت عدم نیاز به این فیلد آن را حدف کنید و یا در آن مقدار بنویسید',
            'form.foods.0.basic_food_id.required' => 'لطفا مواد تشکیل دهنده را انتخاب کنید',
            'form.foods.*.basic_food_id.required' => 'لطفا مواد تشکیل دهنده را به صورت کامل انتخاب کنید',
            'form.foods.*.unit_type.required' => 'لطفا مواد تشکیل دهنده را به صورت کامل انتخاب کنید',
            'form.foods.*.unit.required' => 'لطفا میزان مواد تشکیل دهنده را به صورت کامل انتخاب کنید',
        ];
    }

    public function deletePhoto()
    {

        if ($this->uploadedPhotoUrl) {
            $relativePath = str_replace(Storage::disk('public')->url(''), '', $this->uploadedPhotoUrl);
            Storage::disk('public')->delete($relativePath);
        }

        $this->photo = null;
        $this->uploadedPhotoUrl = null;
    }
    protected function rules()
    {
        // Base validation rules
        $rules = [
            'form.name' => 'required|string|max:225',
            'form.time' => 'required|integer',
            'form.serving' => 'required|integer',
            'form.weight' => 'required|integer',
            'form.calorie' => 'required|integer',
            'form.description' => 'nullable|max:5000',
            'form.difficulty' => 'required|integer',
            'form.category' => 'required',
            'form.recipe.unit' => 'required',
            'form.recipe.gram' => 'required',
            'form.instroduction.0' => 'required',
            'form.foods.0.basic_food_id' => 'required',
            'form.foods.0.unit_type' => 'required',
            'form.foods.0.unit' => 'required',
        ];

        // Dynamic validation for instructions
        for ($i = 1; $i < $this->form['instructionsCounter']; $i++) {
            $rules['form.instroduction.' . $i] = 'required';
        }

        // Dynamic validation for foods
        for ($i = 1; $i < $this->form['countBasicFoods']; $i++) {
            $rules['form.foods.' . $i . '.basic_food_id'] = 'required';
            $rules['form.foods.' . $i . '.unit_type'] = 'required';
            $rules['form.foods.' . $i . '.unit'] = 'required';
        }

        return $rules;
    }

    public function Recepie()
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('updateUi', true);
            throw $e;
        }
        $instroduction = [];
        for ($i = 0; $i < $this->form['instructionsCounter']; $i++) {
            $instroduction[] = [
                'text' => $this->form['instroduction'][$i],
            ];
        }
        $model = [
            'category_id' => $this->form['category'],
            'name' => $this->form['name'],
            'description' => $this->form['description'] ?? null,
            'time' => $this->form['time'],
            'serving' => $this->form['serving'],
            'weight' => $this->form['weight'],
            'calorie' => $this->form['calorie'],
            'difficulty' => $this->form['difficulty'],
            'instructions' => $instroduction,
            'suggested' => isset($this->form['sugested']) ? $this->form['sugested'] : 0,
            'priority' => $this->form['priority'],
            'active' => $this->form['active'],

        ];
        if (isset($this->form['mainFood'])) {
            $model['food_id'] = $this->form['mainFood'];
        }
        if (isset($this->form['recipe']['img'])) {
//            $model['detail'][Recipe::DETAIL_IMAGE] = $this->form['recipe']['img'];
        }
        if (!empty($this->form['photo'])) {
            $temporaryPath = $this->form['photo'];
            $finalPath = str_replace('temp/', '', $temporaryPath);

            Storage::disk('public')->move($temporaryPath, $finalPath);

            // add logo
            $manager = ImageManager::gd();
            $image = $manager->read(public_path('storage/'.$finalPath));
            $icon = $manager->read(public_path('default/logo1.png'));
            $icon2 = $manager->read(public_path('default/logo2.png'));
            $image->place($icon, 'bottom-right', 10, 10);
            $image->place($icon2, 'top-left', 10, 10);
            $image->save(public_path('storage/'.$finalPath));

            $model['detail'][Recipe::DETAIL_IMAGE] = $finalPath;

        }


        $foodFacts = [
            'createdAt' => Carbon::now()->toDateTimeString(),
            'updatedAt' => Carbon::now()->toDateTimeString(),
            'fatAmount' => $this->computData['fat'],
            'ironAmount' => $this->computData['iron'],
            'fiberAmount' => $this->computData['fiber'],
            'sugarAmount' => $this->computData['suger'],
            'sodiumAmount' => $this->computData['sodium'],
            'calciumAmount' => $this->computData['calcium'],
            'calorieAmount' => $this->computData['min_calorie'],
            'proteinAmount' => $this->computData['protein'],
            'phosphorAmount' => $this->computData['phosphor'],
            'transFatAmount' => $this->computData['trans_fat'],
            'magnesiumAmount' => $this->computData['magnesium'],
            'potassiumAmount' => $this->computData['porassium'],
            'cholesterolAmount' => $this->computData['cholestrol'],
            'carbohydrateAmount' => $this->computData['carb'],
            'saturatedFatAmount' => $this->computData['saturated_fat'],
            'monounsaturatedFatAmount' => $this->computData['mono_fat'],
            'polyunsaturatedFatAmount' => $this->computData['mono_fat'],
        ];

        $model['foodFact'] = $foodFacts;
        if ($this->isEdited) {
            $rec = $this->recipe->update($model);
            $this->recipe->units()->updateOrCreate(
                ['recipe_id' => $this->recipe->id],
                [
                    'food_unit_id' => $this->form['recipe']['unit'],
                    'quantity_per_unit' => $this->form['recipe']['gram']
                ]
            );
            $syncData = [];
            foreach ($this->form['foods'] as $basicFood) {
                $syncData[$basicFood['basic_food_id']] = [
                    'food_unit_id' => $basicFood['unit_id'],
                    'quantity_per_unit' => $basicFood['unit'],
                ];
            }
            $this->recipe->basicFoods()->sync($syncData);


            $msg = 'دستور پخت با موفقیت ویرایش شد';
        } else {
            $rec = Recipe::create($model);
            $rec->units()->create([
                'food_unit_id' => $this->form['recipe']['unit'],
                'quantity_per_unit' => $this->form['recipe']['gram']
            ]);
            foreach ($this->form['foods'] as $basicFood) {
                $rec->basicFoods()->attach($basicFood['basic_food_id'], [
                    'food_unit_id' => $basicFood['unit_id'],
                    'quantity_per_unit' => $basicFood['unit'],
                ]);
            }
            $msg = 'دستور پخت با موفقیت اضافه شد';
        }
        return redirect()->route('admin.recipe.list')->with('success', $msg);
    }

    public function addcounter($counterName): void
    {
        $this->form[$counterName] += 1;
        $this->dispatch('updateUi');
    }

    public function removeCounter($counterName, $itemSelected): void
    {
        $this->form[$counterName] -= 1;

        if ($counterName == 'instructionsCounter'){
            unset($this->form['instroduction'][$itemSelected]);
            $this->form['instroduction'] = array_values($this->form['instroduction']);

        } else {
            if (isset($this->form['foods'][$itemSelected])) {
                unset($this->form['foods'][$itemSelected]);
                $this->form['foods'] = array_values($this->form['foods']);
                $this->calculateData();
            }
        }
        $this->dispatch('updateUi');
    }

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
            'suger' => 0,
            'iron' => 0,
            'sodium' => 0,
            'calcium' => 0,
            'phosphor' => 0,
            'trans_fat' => 0,
            'magnesium' => 0,
            'porassium' => 0,
            'cholestrol' => 0,
            'saturated_fat' => 0,
            'mono_fat' => 0,
            'poly_fat' => 0,
            'total_quantity_per_gram' => 0
        ];
        $nutritionKeys = [
            'CALORIE' => 'min_calorie',
            'PROTEIN' => 'protein',
            'CARBOHYDRATE' => 'carb',
            'FIBER' => 'fiber',
            'FAT' => 'fat',
            'IRON' => 'iron',
            'SUGAR' => 'suger',
            'SODIUM' => 'sodium',
            'CALCIUM' => 'calcium',
            'PHOSPHOR' => 'phosphor',
            'TRANS_FAT' => 'trans_fat',
            'MAGNESIUM' => 'magnesium',
            'POTASSIUM' => 'porassium',
            'CHOLESTEROL' => 'cholestrol',
            'SATURATED_FAT' => 'saturated_fat',
            'MONOUNSATURATED_FAT' => 'mono_fat',
            'POLY_UNSATURATED_FAT' => 'poly_fat',
        ];
        $res = [];

        $totalQuantityPerGram = 0;
        foreach ($this->form['foods'] as $i => $food) {
            if (!isset($food['basic_food_id']) || !isset($this->form['foods'][$i]['unit_type']) || !isset($this->form['foods'][$i]['unit'])) {
                continue;
            }
            $basicFood = BasicFood::find($food['basic_food_id']);
            $unit_type = $this->form['foods'][$i]['unit_type'];
            $numberEachUnit = $this->form['foods'][$i]['unit'];
            $quantityPerGram = 0;

            // If the unit is per gram
            if ($unit_type == 3) {
                $quantityPerGram = $numberEachUnit;
                $quantityPerUnit = 1;
            } else {
                $quantityPerUnit = $basicFood->units()->where('food_unit_id', $unit_type)->first()?->pivot->quantity_per_unit;
                $quantityPerGram = $quantityPerUnit * $numberEachUnit;
            }
            $this->computData['total_quantity_per_gram'] += $quantityPerGram;


            $unit = $food['unit'] ?? 1;
//            dd($quantity_per_unit);
            if ($quantityPerGram) {
                // Calculate nutritional values
                foreach ($nutritionKeys as $foodFactKey => $computDataKey) {
                    if(!isset($basicFood->food_fact[$foodFactKey])){
                        continue ;
                    }
                    $quantityFoodFact = (int)$basicFood->food_fact[$foodFactKey];
                    if ($quantityFoodFact <= 0) {
                        $this->computData[$computDataKey] += 0;
                    } else {

                        if ($unit_type == 3) {
                            $valuePerUnit = (($quantityPerGram * $quantityFoodFact) / 100);
                        } else {
//                            $valuePerUnit = (($quantityPerGram * $quantityFoodFact) / $quantityPerUnit); // old
                            $valuePerUnit = ($quantityFoodFact / 100) * $quantityPerGram;
                        }

                        $this->computData[$computDataKey] = round(($this->computData[$computDataKey] ?? 0) + $valuePerUnit, 2);
                        $res[$computDataKey][] = $this->computData[$computDataKey];
                    }
                }

                $this->computData['recipe_list'][] = '';

            }
        }
//        dd($res);


        $this->form['weight'] = (int) $this->computData['total_quantity_per_gram'];
        $this->form['calorie'] = (int) $this->computData['min_calorie'];
        if (isset($this->computData['recipe_list'])) {
            $this->computData['recipe'] = implode("<hr>", array_filter($this->computData['recipe_list']));
        } else {
            // Handle the case when 'recipe_list' does not exist
            $this->computData['recipe'] = ''; // or any default value you want
        }
    }

    #[On('addBasicFood')]
    public function addBasicFood($basicFoodId = 0, $foodNumber = 0)
    {

        $baseFood = BasicFood::find($basicFoodId);
        $this->form['foods'][$foodNumber]['basic_food_id'] = $basicFoodId;
        // auto select first unit
        $this->form['foods'][$foodNumber]['unit'] = 0;
        $this->form['foods'][$foodNumber]['max'] = '';
        $this->form['foods'][$foodNumber]['units'] = $baseFood->units()->get();
        $this->form['foods'][$foodNumber]['unit_selected'] = $baseFood->units()->where('is_primary', '1')->first()?->id ?? null;

        // auto select first unit
        //        $this->calculateData();
        $this->dispatch('updateUi');
    }

    #[On('addUnitType')]
    public function addUnitType($unitTypeId = 0, $unitNumber = 0)
    {
        $this->form['foods'][$unitNumber]['unit'] = null;
        $this->form['foods'][$unitNumber]['unit_type'] = $unitTypeId;
        $this->form['foods'][$unitNumber]['unit_name'] = FoodUnit::find($unitTypeId)?->name;
        $this->form['foods'][$unitNumber]['unit_id'] = FoodUnit::find($unitTypeId)?->id;
        $this->dispatch('updateUi');
    }

    public function updated($name, $value): void
    {
        //when change unit base food
        if (preg_match('/\b(unit|max)\b/', $name)) {
            $this->calculateData();
        }
        $this->dispatch('updateUi');
    }

    public function fillTheform()
    {
        $this->isEdited = true;
        $this->form['category'] = $this->recipe->category_id;
        $this->form['name'] = $this->recipe->name;
        $this->form['time'] = $this->recipe->time;
        $this->form['description'] = $this->recipe->description;
        $this->form['serving'] = $this->recipe->serving;
        $this->form['weight'] = $this->recipe->weight;
        $this->form['calorie'] = $this->recipe->calorie;
        $this->form['difficulty'] = (int)$this->recipe->difficulty->value;
        $this->form['suggested'] = $this->recipe->suggested;
        $this->form['priority'] = $this->recipe->priority;
        $this->form['active'] = $this->recipe->active;
        $this->form['mainFood'] = $this->recipe->food_id;
        $instructions = $this->recipe->instructions;
//        if (isset($this->recipe->detail[Recipe::DETAIL_IMAGE])) {
//            $this->form['recipe']['img'] = $this->recipe->detail[Recipe::DETAIL_IMAGE];
//        }

        if (isset($this->recipe->detail[Recipe::DETAIL_IMAGE])) {

            $this->uploadedPhotoUrl = Storage::disk('public')->url($this->recipe->detail[Recipe::DETAIL_IMAGE]);
            $this->uploadedFileName = basename($this->recipe->detail[Recipe::DETAIL_IMAGE]);
            $this->uploadedFileType = getFileIconClass(pathinfo($this->recipe->detail[Recipe::DETAIL_IMAGE], PATHINFO_EXTENSION));
        } else {
            $this->uploadedPhotoUrl = null;
        }

        $this->form['instructionsCounter'] = count($instructions);
        foreach ($instructions as $key => $instruction) {
            $this->form['instroduction'][$key] = $instruction['text'];
        }
        $foodUnit = $this->recipe->units->first();
        $this->form['recipe']['unit'] = $foodUnit->food_unit_id;
        $this->form['recipe']['gram'] = $foodUnit->quantity_per_unit;
        $basicFoods = $this->recipe->basicFoods;
        foreach ($basicFoods as $key => $basicFood) {
            if ($basicFood->id == '666') {
                //                dd($basicFood->pivot->food_unit_id);
            }
            $unit = FoodUnit::find($basicFood->pivot->food_unit_id);
            $this->form['foods'][$key]['basic_food_id'] = $basicFood->id;
            $this->form['foods'][$key]['unit_name'] = $unit->name;
            $this->form['foods'][$key]['unit_selected'] = $basicFood->pivot->food_unit_id;
            $this->form['foods'][$key]['units'] = $basicFood->units()->get();
            $this->form['foods'][$key]['unit_id'] = $basicFood->pivot->food_unit_id;
            $this->form['foods'][$key]['unit_type'] = $basicFood->pivot->food_unit_id;
            $this->form['foods'][$key]['unit'] = $basicFood->pivot->quantity_per_unit;
        }
        $this->form['countBasicFoods'] = count($basicFoods);
        $this->calculateData();
    }

    public function mount()
    {
        $this->fetchData['categories'] = RecipeCategory::all();
        $this->fetchData['BasicFoods'] = BasicFood::active()->get();
        $this->fetchData['foods'] = Food::whereNull('parent_id')->get();
        $this->fetchData['fooduits'] = FoodUnit::all();
        $this->form['priority'] = Recipe::maxOrder();
        $recipe = request()->route('recipe');
        if ($recipe instanceof Recipe) {
            $this->fillTheform();
        }
    }

    public function render()
    {
        return view('diet::livewire.admin.recipe.recipe-update-or-create');
    }
}
