<?php

namespace Modules\Diet\Livewire\Admin\PrescribedDiets;

use Hekmatinasser\Verta\Verta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Diet\Entities\DietRequest;
use Modules\User\Entities\User;
use Throwable;

class DietApprovalReport extends Component
{
    use WithPagination;

    #[Url]
    public array $search = [];

    public array $dateSearch = [];

    public function mount(): void
    {
        $this->dateSearch = [
            'fromDate' => $this->search['fromDate'] ?? '',
            'toDate' => $this->search['toDate'] ?? '',
        ];
    }

    public function startSearch(): void
    {
        $this->resetValidation();

        $fromDate = $this->parseJalaliDate($this->dateSearch['fromDate'] ?? null, 'dateSearch.fromDate');
        $toDate = $this->parseJalaliDate($this->dateSearch['toDate'] ?? null, 'dateSearch.toDate');

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        if ($fromDate && $toDate && $fromDate > $toDate) {
            $this->addError('dateSearch.toDate', 'تاریخ پایان باید مساوی یا بعد از تاریخ شروع باشد.');
            return;
        }

        $this->search['fromDate'] = $this->dateSearch['fromDate'] ?? '';
        $this->search['toDate'] = $this->dateSearch['toDate'] ?? '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'dateSearch');
        $this->resetValidation();
        $this->resetPage();
        $this->dispatch('approval-report-dates-reset');
    }

    public function showApprover(int $approverId): void
    {
        $this->search['approver_id'] = (string) $approverId;
        $this->resetPage();
    }

    protected function parseJalaliDate(?string $date, string $field): ?string
    {
        if (blank($date)) {
            return null;
        }

        $date = strtr(trim($date), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        if (!preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $date)) {
            $this->addError($field, 'تاریخ را با قالب شمسی صحیح وارد کنید.');
            return null;
        }

        try {
            return Verta::parse($date)->toCarbon()->toDateString();
        } catch (Throwable) {
            $this->addError($field, 'تاریخ شمسی واردشده معتبر نیست.');
            return null;
        }
    }

    protected function approvedDietsQuery(): Builder
    {
        $query = DietRequest::query()
            ->without('dietRequestDetails')
            ->whereNotNull('expert_approved_by')
            ->whereNotNull('expert_approved_at');

        if (filled($this->search['approver_id'] ?? null)) {
            $query->where('expert_approved_by', (int) $this->search['approver_id']);
        }

        $fromDate = $this->parseJalaliDate($this->search['fromDate'] ?? null, 'dateSearch.fromDate');
        $toDate = $this->parseJalaliDate($this->search['toDate'] ?? null, 'dateSearch.toDate');

        if ($fromDate) {
            $query->where('expert_approved_at', '>=', $fromDate . ' 00:00:00');
        }

        if ($toDate) {
            $query->where('expert_approved_at', '<=', $toDate . ' 23:59:59');
        }

        return $query;
    }

    protected function adminUsers(): Collection
    {
        return User::query()
            ->whereHas('roles', function (Builder $query) {
                $query
                    ->whereHas('permissions', fn (Builder $permissions) => $permissions->where('name', 'ADMIN_ACCESS'))
                    ->orWhereHas('permissions', fn (Builder $permissions) => $permissions->where('name', 'SUPER_ADMIN'));
            })
            ->with('roles:id,name')
            ->orderBy('id')
            ->get();
    }

    public function render()
    {
        $adminUsers = $this->adminUsers();
        $summaryData = $this->approvedDietsQuery()
            ->selectRaw('expert_approved_by AS approver_id')
            ->selectRaw('COUNT(*) AS total_approved')
            ->selectRaw('MAX(expert_approved_at) AS last_approved_at')
            ->groupBy('expert_approved_by')
            ->get()
            ->keyBy(fn ($row) => (int) $row->approver_id);

        $approverIds = $summaryData->keys()
            ->merge($adminUsers->pluck('id'))
            ->unique()
            ->values();

        $approversById = User::query()
            ->whereIn('id', $approverIds)
            ->with('roles:id,name')
            ->get()
            ->keyBy('id');

        $summary = $approverIds
            ->when(
                filled($this->search['approver_id'] ?? null),
                fn (Collection $ids) => $ids->filter(
                    fn ($id) => (int) $id === (int) $this->search['approver_id']
                )
            )
            ->map(function ($approverId) use ($approversById, $summaryData) {
                $approverId = (int) $approverId;
                $data = $summaryData->get($approverId);

                return (object) [
                    'approver_id' => $approverId,
                    'user' => $approversById->get($approverId),
                    'total_approved' => (int) ($data->total_approved ?? 0),
                    'last_approved_at' => $data->last_approved_at ?? null,
                ];
            })
            ->sortByDesc('total_approved')
            ->values();

        $diets = $this->approvedDietsQuery()
            ->select([
                'id',
                'user_id',
                'diet_plan_id',
                'expert_approved_by',
                'expert_approved_at',
            ])
            ->with(['user', 'dietPlan', 'expertApprover'])
            ->orderByDesc('expert_approved_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('diet::livewire.admin.prescribed-diets.diet-approval-report', [
            'availableApprovers' => $approverIds->map(
                fn ($approverId) => (object) [
                    'id' => (int) $approverId,
                    'user' => $approversById->get((int) $approverId),
                ]
            ),
            'summary' => $summary,
            'diets' => $diets,
            'totals' => [
                'approved' => $summary->sum('total_approved'),
            ],
        ]);
    }
}
