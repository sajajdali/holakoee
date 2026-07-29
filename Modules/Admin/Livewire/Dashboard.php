<?php

namespace Modules\Admin\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Hekmatinasser\Verta\Verta;
use Livewire\Attributes\Title;
use Modules\Chat\app\Models\Chat;
use Modules\User\Entities\User;
use Modules\Diet\Entities\DietRequest;
use Modules\Diet\Enum\DietRequestStatusEnum;
use Modules\Transaction\Entities\Transaction;
use Modules\Transaction\Enum\TransactionStatusEnum;

#[title('پیشخوان مدیریت')]
class Dashboard extends Component
{
    //transactions prop
    public $transactionsCostToday, $transactionPrecent,$transactions;

    //diet Prop
    public $todaydiets, $endedDiet;
    public $newQuestionsCount = 0;

    //user Prop
    public $registeredUsers;

    //chart Transactions prop
    public array $chartData;
    public $transactiosnYearlyCost;
    protected function transactionCaculation()
    {
        $this->transactionsCostToday = Transaction::whereDate('created_at', Carbon::now()->today())
            ->where('status', TransactionStatusEnum::SUCCESSFUL)?->sum('total_cost') ?? 0;
        $transactionsCostyesterday = Transaction::whereDate('created_at', Carbon::now()->yesterday())
            ->where('status', TransactionStatusEnum::SUCCESSFUL)?->sum('total_cost') ?? 0;
        if ($transactionsCostyesterday != 0 &&  $this->transactionsCostToday != 0) {
            $transactionProgress = (($this->transactionsCostToday / $transactionsCostyesterday) -  $this->transactionsCostToday);
            if ($transactionProgress > 0) {
                $this->transactionPrecent = ['amount' => ($transactionProgress * 100), 'status' => '+'];
            } else {
                $this->transactionPrecent =  ['amount' => (($this->transactionPrecent - 1) * 100), 'status' => '-'];
            }
        } else {
            $this->transactionPrecent =  ['amount' => 0, 'status' => ''];
        }

        $this->transactiosnYearlyCost  =  Transaction::whereBetween('created_at', [Carbon::now(), Carbon::now()->subYear()])
            ->where('status', TransactionStatusEnum::SUCCESSFUL)?->sum('total_cost') ?? 0;

            $this->transactions = Transaction::where('status', TransactionStatusEnum::SUCCESSFUL)->orderByDesc('id')?->get();
    }
    protected function dietPlan()
    {
        $this->todaydiets = DietRequest::where('status', DietRequestStatusEnum::ACTIVE)
            ->whereDate('updated_at', Carbon::now()
                ->today())?->count() ?? 0;
        $this->endedDiet = DietRequest::where('status', DietRequestStatusEnum::END)
            ->whereDate('updated_at', Carbon::now()
                ->today())?->count() ?? 0;
    }

    protected function UserRegisteredNumber()
    {
        $this->registeredUsers = User::whereBetween('created_at', [Verta::now()->subWeek(), Verta::now()])?->count();
    }
    protected function chartData()
    {
        $transactionGroup = Transaction::where('status', TransactionStatusEnum::SUCCESSFUL)
            ->whereBetween('created_at', [Carbon::now()->subYear(), Carbon::now()])
            ->get()
            ->groupBy(function ($item) {
                // ماه جلالی به‌صورت نام (مثلاً فروردین، اردیبهشت، ...)
                return Verta::instance($item->created_at)->format('%B');
            });

        $this->chartData = [];

        foreach ($transactionGroup as $month => $groupedTransactions) {
            $this->chartData['mount'][] = $month; // Month name
            $this->chartData['amount'][] = $groupedTransactions->sum('total_cost'); // Sum of the 'cost' column
        }
    }
    public function mount()
    {
        $user = auth()->user();

        $this->newQuestionsCount = Chat::query()
            ->where('new_message_by_user', '>', 0)
            ->when($user->isNutritionists(), fn ($q) =>
            $q->where('assigned_to', $user->id)
            )
            ->count();

        $this->transactionCaculation();
        $this->dietPlan();
        $this->UserRegisteredNumber();
        $this->chartData();
    }
    public function render()
    {
        return view('admin::livewire.dashboard');
    }
}
