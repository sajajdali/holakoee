<?php

namespace Modules\Diet\Livewire\Admin\PrescribedDiets;

use Hekmatinasser\Verta\Verta;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Diet\Entities\DietPlan;
use Modules\Diet\Entities\DietRequest;
use Modules\User\Enum\UserMetaEnum;

class PresCribedDietsList extends Component
{
    use WithPagination;

    public $searchPanel = '';

    #[Url]
    public $search = [];
    public $dateSearch = [];


    public function startSearch()
    {
        $this->resetPage();
        if (!empty($this->dateSearch['requestDate'])) {
            $this->search['requestDate'] = $this->dateSearch['requestDate'];
        }
        if (!empty($this->dateSearch['sendDate'])) {
            $this->search['sendDate'] = $this->dateSearch['sendDate'];
        }
        if (!empty($this->dateSearch['endDate'])) {
            $this->search['endDate'] = $this->dateSearch['endDate'];
        }
    }

    public function resetProperties()
    {
        $this->search = [];
        $this->dateSearch = [];
        $this->resetPage();
    }

    #[On('delete')]
    public function deleteDiet($id)
    {
        DietRequest::findOrFail($id)->delete();
        session()->flash('success', 'رژیم با موفقیت حذف شد.');
        $this->resetPage();
    }

    public function render()
    {
        $authId = auth()->id();
        $isNutritionist = auth()->user()->roles->contains('id', nutritionRolId());

        $diet = DietRequest::query()
            ->when($isNutritionist, function ($query) use ($authId) {
                $query->whereHas('user.nutritionists', function ($q) use ($authId) {
                    $q->where('nutritionist_user.nutritionist_id', $authId);
                });
            })

            // ID
            ->when(filled($this->search['id'] ?? null) && (int) ($this->search['id'] ?? 0) !== 0, function ($query) {
                return $query->where('id', 'like', '%' . trim($this->search['id']) . '%');
            })

            // Diet plan (select returns id)
            ->when(filled($this->search['name'] ?? null), function ($query) {
                return $query->where('diet_plan_id', (int) $this->search['name']);
            })

            // Dates
            ->when(filled($this->search['requestDate'] ?? null), function ($query) {
                return $query->whereDate('created_at', Verta::parse($this->search['requestDate'])->toCarbon()->toDateString());
            })
            ->when(filled($this->search['sendDate'] ?? null), function ($query) {
                return $query->whereDate('updated_at', Verta::parse($this->search['sendDate'])->toCarbon()->toDateString());
            })
            ->when(filled($this->search['endDate'] ?? null), function ($query) {
                return $query->whereDate('end_date', Verta::parse($this->search['endDate'])->toCarbon()->toDateString());
            })

            // Status (enum casted)
            ->when(isset($this->search['status']) && $this->search['status'] !== '', function ($query) {
                return $query->where('status', (int) $this->search['status']);
            })

            // User filters: mobile + first_name(meta) + last_name(meta)
            ->when(
                filled($this->search['mobile'] ?? null)
                || filled($this->search['first_name'] ?? null)
                || filled($this->search['last_name'] ?? null),
                function ($query) {

                    $mobile = trim($this->search['mobile'] ?? '');
                    $first  = trim($this->search['first_name'] ?? '');
                    $last   = trim($this->search['last_name'] ?? '');

                    return $query->whereHas('user', function ($u) use ($mobile, $first, $last) {

                        // mobile column in users table
                        if ($mobile !== '') {
                            $u->where('mobile', 'like', "%{$mobile}%");
                        }

                        // first name meta
                        if ($first !== '') {
                            $u->whereHas('metas', function ($m) use ($first) {
                                $m->where('meta_key', UserMetaEnum::FIRST_NAME)
                                    ->where('meta_value', 'like', "%{$first}%");
                            });
                        }

                        // last name meta
                        if ($last !== '') {
                            $u->whereHas('metas', function ($m) use ($last) {
                                $m->where('meta_key', UserMetaEnum::LAST_NAME)
                                    ->where('meta_value', 'like', "%{$last}%");
                            });
                        }
                    });
                }
            )

            ->orderByDesc('id')
            ->paginate(20);
        return view('diet::livewire.admin.prescribed-diets.pres-cribed-diets-list', [
            'diets' => $diet
        ]);
    }
}
