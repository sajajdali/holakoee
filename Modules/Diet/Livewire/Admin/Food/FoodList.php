<?php

namespace Modules\Diet\Livewire\Admin\Food;

use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Core\Entities\Disease;
use Modules\Diet\Entities\Condition;
use Modules\Diet\Entities\Food;
use Modules\Diet\Enum\ConditionApplyItemEnum;
use Modules\Diet\Livewire\Admin\Traits\ConditionTrait;

#[title('مدیریت غذا ها')]

class FoodList extends Component
{


    use withPagination,ConditionTrait;

    #[Url]
    public $search = [];
    public $searchPanel = '';
    #[Url]
    public $samleDietList = null;
    #[Url]
    public $page = 1;
    public $old_page = 1;
    #[Url]
    public $parent_id = null;
    public $food = null;
    public $iteration = 20;
    public $changePage = false;

    public $selectAll = false;
    public $selectedFoods = [];

    #[On('delete')]
    public function delete(Food $model)
    {
        $this->authorize('delete', $model);
        $model->delete();

        //update max  calorie when delete child
        if ($model->parent_id != null){
            $parentFood = Food::find($model->parent_id);
            $findMaxChildCalorie = $parentFood->children()->max('calories');
            if ($findMaxChildCalorie < $parentFood->max_calories){
                $parentFood->update(['max_calories' => $findMaxChildCalorie]);
            }
        }



//        return redirect()->route('admin.food.index')->with('success', 'غذا با موفقیت حذف شد.');
    }


    public function getDietFood(Food $food)
    {
        return app('dietService')->complexFoodResult($food);
    }

    public function startSearch()
    {
        $this->resetPage();
    }


    public function duplicateRow($food)
    {
        $food = Food::find($food);

        // Get meal IDs directly from the database
        $FoodMeals = $food->meals()->pluck('id')->toArray();

        // Initialize conditions array
        $conditions = [];

        // Fetch conditions directly from the database and convert to associative array
        $food->load('conditions');
        foreach ($food->conditions as $condition) {
            $conditions[$condition->id] = $this->convertToAssociativeArray(json_decode($condition->pivot->options));
        }

        $basicFoodList = $food->basicFoodPivot()->orderBy('basic_food_pivot.id')->get();
        $basicFoodListArray = [];
        if (count($basicFoodList)){
            $i = 0;
            foreach ($basicFoodList as $basicFood){
                $basicFoodListArray['foods'][$i]['basic_food_id'] = $basicFood->id;
                $basicFoodListArray['foods'][$i]['unit_name'] = $basicFood->units()->where('is_primary' , '1')->first()?->name ?? $basicFood->units()->first()->name;
                $basicFoodListArray['foods'][$i]['unit'] = $basicFood->pivot->quantity;
                $basicFoodListArray['foods'][$i]['max'] = $basicFood->pivot->maximum;
                $i++;
            }
        }

        // Create a new instance of BasicFood and save it
        $newFood = $food->replicate();
        $newFood->save();

        // Sync meals if there are any
        if (!empty($FoodMeals)) {
            $newFood->meals()->sync($FoodMeals);
        }

        //insert basic foods
        if ($basicFoodList->count()) {
            foreach ($basicFoodListArray['foods'] as $basicFood) {
                $newFood->basicFoodPivot()->syncWithPivotValues([$basicFood['basic_food_id']], [
                    'quantity' => ($basicFood['unit'] == "" || $basicFood['unit'] == null) ? 1 : $basicFood['unit'],
                    'maximum' => ($basicFood['max'] == "") ? null : $basicFood['max']
                ], false);
            }
        }

        // Attach conditions if there are any
        foreach ($conditions as $keyCondition => $valueCondition) {
            // Filter the array to keep only true values
            $filteredArray = array_filter($valueCondition, function ($value) {
                return $value === true;
            });

            // Attach the condition with filtered options
            $newFood->conditions()->attach($keyCondition, ['options' => json_encode(array_keys($filteredArray), JSON_UNESCAPED_UNICODE)]);
        }
        return redirect()->route('admin.food.edit' , $newFood)->with('success', 'از این غذا کپی ایجاد شد و شما در حال ویرایش آن هستید');
    }


    public function updatedPage($page)
    {
        // زمانی که صفحه تغییر کند
        $this->dispatch('changePage', ['page' => $page]);

        $this->selectedFoods = [];

    }
    public function deleteSelected()
    {
        Food::destroy($this->selectedFoods);

        // Optionally refresh the list of foods
        $this->foods = Food::all();
        $this->selectAll = false;
        $this->selectedFoods = [];
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedFoods = $this->foods->pluck('id')->toArray();
        } else {
            $this->selectedFoods = [];
        }
    }

    public function getFoodName()
    {
        return Food::find($this->parent_id)?->name;
    }
    public function render()
    {
        $foods = Food::where('parent_id', $this->parent_id)
            ->when(isset($this->search['id']) && (int) $this->search['id'] !== 0, function ($query) {
                return $query->where('id', $this->search['id']);
            })
            ->when(isset($this->search['name']) && !empty($this->search['name']), function ($query) {
                return $query->where('name', 'LIKE', "%{$this->search['name']}%");
            })
            ->when(isset($this->search['meal']) && !empty($this->search['meal']), function ($query) {
                return $query->whereHas('meals', fn($q) => $q->where('id', $this->search['meal']));
            });

        if (isset($this->search['conditions']) && !empty($this->search['conditions'])) {
            $foods = app('dietService')->handleConditionFoodSearch($foods, $this->search['conditions']);
        }


        $order = $this->parent_id != null ? 'ASC' : 'DESC';
        $foods = $foods->orderBy('id' , $order);
        $foods = $foods->paginate(40);

        $conditions = Condition::whereJsonContains('apply_to', ConditionApplyItemEnum::FOOD->value)->get();
        $disease = Disease::active()->get();
        return view('diet::livewire.admin.food.food-list', compact('foods', 'conditions', 'disease'));
    }

    public  function getSpecificDays($startDate, $dayType, $numDays)
    {
        $days = [];
        $currentDate = Carbon::parse($startDate);

        $dayType = strtolower($dayType);
        $desiredDays = [];

        if ($dayType == 'odd') {
            $desiredDays = ['Sunday', 'Tuesday', 'Thursday'];
        } elseif ($dayType == 'even') {
            $desiredDays = ['Saturday', 'Monday', 'Wednesday'];
        }

        while (count($days) < $numDays) {
            $dayOfWeek = $currentDate->format('l');

            if (in_array($dayOfWeek, $desiredDays) && !$currentDate->isFriday()) {
                $days[] = $currentDate->format('Y/m/d');
            }

            $currentDate->addDay();
        }

        return $days;
    }

}
