<?php

namespace Modules\Diet\Livewire\Admin\PrescribedDiets\Details;

use Livewire\Component;
use Modules\Core\Entities\Disease;
use Modules\Diet\Entities\Condition;
use Modules\Diet\Entities\DietRequest;
use Modules\Diet\Enum\ConditionApplyItemEnum;
use Modules\Diet\Enum\ConditionKeyEnum;
use Modules\Diet\Service\DietService;

class UserConditions extends Component
{
    public $dietReqestId;
    public $conditions;
    public ?DietRequest $dietRequest;
    public $successFully = null;
    public bool $changeConditions = false;
    public ?array $disease;
    public array $fetchData = [];

    public array $form = [];
    protected $rules = [
        'form.name' => 'required|string',
    ];
    protected $messages = [
        'form.name.required' => 'نام غذا را وارد کنید',
    ];

    public function editConditions()
    {
        $this->successFully = null;
        $this->changeConditions = !$this->changeConditions;
    }

    public function mount()
    {

        $this->dietRequest = (DietRequest::find($this->dietReqestId));
        $this->conditions = $this->dietRequest->detail;
        $userConditions = $this->conditions['condition'];

        $this->fetchData['disease'] = Disease::active()->get();

        $this->fetchData['conditions'] = Condition::whereJsonContains('apply_to', ConditionApplyItemEnum::FOOD->value)->get();
        foreach ($this->fetchData['conditions'] as $value) {

            // remove all disease in list checked
            if ($value->key == ConditionKeyEnum::DISEASE) {
                $opts = (array) $value->options;
                $i = 0;
                foreach ($this->fetchData['disease'] as $disease){
                    $opts['checked'][$i+1] = true;
                    $opts['items']['keys'][$i+1] = $i;
                    $opts['items']['values'][$i+1] = $i;
                    $i++;
                }
                $value->options = $opts;
            }

            // remove all disease in list checked

            foreach ($value->options['checked'] as $k => $check) {
                if (isset($userConditions[$value->key->value])) {
                    $arrayCheck = is_array($userConditions[$value->key->value]) ? $userConditions[$value->key->value] : [];
                } else {
                    $arrayCheck = [];
                }

                $this->form['conditions'][$value->id][$value->options['items']['keys'][$k]] = in_array($k, $arrayCheck) == true ?? false;
            }
        }

        // change for diseases
        if (count($this->form['conditions']) > 0) {
            if (array_key_exists(ConditionKeyEnum::DISEASE->value, $this->form['conditions'])) {
                $diseasesCondition = [];
                $i = 0;
                foreach ($this->form['conditions'][ConditionKeyEnum::DISEASE->value] as $condition) {
                    $i++;
                    $diseasesCondition[$i] = $condition;
                }
                $this->form['conditions'][ConditionKeyEnum::DISEASE->value] = $diseasesCondition;
            }
        }
//        dd($this->form['conditions'][ConditionKeyEnum::DISEASE->value]);

        $this->disease = [];
        if (!empty($this->conditions['condition']) && array_key_exists(ConditionKeyEnum::DISEASE->value, $this->conditions['condition'])) {
            foreach ($this->conditions['condition'][ConditionKeyEnum::DISEASE->value] as $diseasseId) {
                $this->disease[] = Disease::where('id', $diseasseId)->first()->name;
            }
        }
    }

    private function changeConditionIdToKey($conditionLists)
    {
        $list = [];
        if (count($conditionLists) > 0){
            foreach ($conditionLists as $key => $conditionList) {
                $conditionKey = Condition::find($key)?->key;
                if (!$conditionKey) {
                    continue;
                }
                $list[$conditionKey->value] = $conditionList;
            }
        }
        return $list;
    }

    public function saveConditions()
    {
        $detail = $this->dietRequest->detail;

        if (!isset($detail['oldUserCondition'])) {
            $detail['oldUserCondition'] = $detail['condition'] ?? [];
        }

        //  change condition id and find condition key
        $this->form['conditions'] = $this->changeConditionIdToKey($this->form['conditions']);

        $newConditions = [];
        foreach ($this->form['conditions'] as $keyCondition => $valueCondition) {
            $filteredArray = array_filter($valueCondition, function ($value) {
                return $value === true;
            });

            $newConditions[$keyCondition] = array_keys($filteredArray);
        }

        $detail['condition'] = $newConditions;

        $this->dietRequest->detail = $detail;
        $this->dietRequest->save();

        $this->conditions = $this->dietRequest->detail;;
        $this->successFully = 'شرایط کاربر با موفقیت ویرایش شد';
        $this->changeConditions = false;

        $this->disease = [];
        if (!empty($this->conditions['condition']) && array_key_exists(4, $this->conditions['condition'])) {
            foreach ($this->conditions['condition'][4] as $diseasseId) {
                $this->disease[] = Disease::where('id', $diseasseId)->first()->name;
            }
        }
    }

    public function render()
    {
        return view('diet::livewire.admin.prescribed-diets.details.user-conditions');
    }
}
