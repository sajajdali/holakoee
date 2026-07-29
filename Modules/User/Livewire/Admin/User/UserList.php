<?php

namespace Modules\User\Livewire\Admin\User;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Package\Entities\Package;
use Modules\Setting\Enum\SettingKeyEnum;
use Modules\User\app\Notifications\SmsNotification;
use Modules\User\Entities\User;
use Modules\User\Enum\UserMetaEnum;
use Spatie\Permission\Models\Role;

use App\Services\PackageAssigner;

#[Title('مدیریت کاربران')]
class UserList extends Component
{
    use WithPagination, AuthorizesRequests;

    #[Url]
    public $search = [];
    public $searchPanel = '';

    // Quick Sale state
    public string $quickSaleMobile = '';
    public ?int $quickSalePackageId = null;
    public array $packageOptions = [];
    public ?User $existingUser = null;
    public $agents = null;
    public $salesManagers = null;

    public function mount(): void
    {
        $this->agents = User::whereHas('roles', function ($q) {
            $q->where('id', 3);
        })->get();
        $this->salesManagers = User::whereHas('roles', function ($q) {
            $q->where('id', 4); // فرض بر این است که نقش مدیر فروش (Sales Manager) با ID = 4 است
        })->get();

        $this->packageOptions = Package::query()
            ->when(method_exists(Package::class, 'scopeActive'), fn($q) => $q->active())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($p) => ['id' => $p->id, 'title' => $p->name])
            ->toArray();
    }

    public function openQuickSale(): void
    {
        $this->authorize('create', User::class);
        $this->resetQuickSale();
        $this->existingUser = null;
        $this->dispatch('show-quick-sale-modal');
    }

    protected function resetQuickSale(): void
    {
        $this->resetErrorBag(['quickSaleMobile', 'quickSalePackageId', 'userExists']);
        $this->quickSaleMobile = '';
        $this->quickSalePackageId = null;
    }

    public function saveQuickSale(): void
    {
        $this->authorize('create', User::class);

        $this->validate([
            'quickSaleMobile' => ['required', 'string', 'regex:/^\+?\d{10,15}$/'],
            'quickSalePackageId' => ['required', 'integer', 'exists:packages,id'],
        ], [
            'quickSaleMobile.regex' => 'فرمت شماره موبایل صحیح نیست.',
            'quickSalePackageId.required' => 'انتخاب پکیج الزامی است.',
        ]);

        // چک وجود کاربر
        $existing = User::where('mobile', $this->quickSaleMobile)->first();
        if ($existing) {
            $this->existingUser = $existing;
            $this->addError('userExists', 'این کاربر با این شماره در سیستم موجود است. اگر موافقی، همین پکیج را برای او ثبت کنم.');
            $this->dispatch('show-quick-sale-modal');
            return;
        }

        /** @var User $user */
        $user = User::create([
            'mobile' => $this->quickSaleMobile,
            'email' => $this->quickSaleMobile . '@test.com',
            'password' => Hash::make(Str::random(18)),
        ]);

        if ($defaultRole = Role::find(2)) {
            $user->syncRoles([$defaultRole->name]);
            $user->salesManagers()->sync([auth()->user()->id]);
        }

        $package = Package::find($this->quickSalePackageId);
        if (!$package) {
            $this->addError('quickSalePackageId', 'پکیج انتخابی معتبر نیست.');
            $this->dispatch('show-quick-sale-modal');
            return;
        }

        PackageAssigner::assign($user, $package);

        //assign agent
        $agent = User::whereHas('roles', function ($q) {
            $q->where('id', 3);
        })
            ->withCount('clients')
            ->orderBy('clients_count', 'asc')
            ->inRandomOrder()
            ->first();

        if ($agent) {
            $user->nutritionists()->syncWithoutDetaching([$agent->id]);
        }

        session()->flash('success', 'فروش سریع با موفقیت ثبت شد.');
        $this->dispatch('hide-quick-sale-modal');
        $this->resetPage();
    }

    public function confirmAssignPackage(): void
    {
        $this->authorize('create', User::class);

        if (!$this->existingUser) {
            $this->addError('userExists', 'کاربر معتبر نیست.');
            $this->dispatch('show-quick-sale-modal');
            return;
        }

        $this->validate([
            'quickSalePackageId' => ['required', 'integer', 'exists:packages,id'],
        ], [
            'quickSalePackageId.required' => 'انتخاب پکیج الزامی است.',
        ]);

        $package = Package::find($this->quickSalePackageId);
        if (!$package) {
            $this->addError('quickSalePackageId', 'پکیج انتخابی معتبر نیست.');
            $this->dispatch('show-quick-sale-modal');
            return;
        }

        $already = \Modules\Package\Entities\PackageUser::where('user_id', $this->existingUser->id)
            ->where('package_id', $package->id)
            ->where('type', \Modules\Package\Enum\PackageUserTypeEnum::IN_USE)
            ->exists();
        if ($already) {
            $this->addError('quickSalePackageId', 'این پکیج از قبل برای این کاربر در حال استفاده است.');
            $this->dispatch('show-quick-sale-modal');
            return;
        }

        PackageAssigner::assign($this->existingUser, $package);

        session()->flash('success', 'بسته برای کاربر موجود با موفقیت ثبت شد.');
        $this->existingUser = null;
        $this->dispatch('hide-quick-sale-modal');
        $this->resetPage();
    }

    public function sendSmsAgain(int $userId): void
    {
        $user = User::find($userId);

        if (!$user) {
            $this->dispatch('sms-sent-failed', message: 'کاربر مورد نظر یافت نشد.');
            return;
        }

        $template = setting(SettingKeyEnum::SMS_AFTER_QUICK_SALE);

        if (!$template) {
            $this->dispatch('sms-sent-failed', message: 'قالب پیامک تنظیم نشده است.');
            return;
        }

        try {
            $sendParameter = [
                $userId, // هر پارامتری که قالب پیامک شما نیاز دارد
            ];

            $user->notify(new SmsNotification($template, $sendParameter));

            $this->dispatch('sms-sent-success');
        } catch (\Throwable $e) {
            $this->dispatch('sms-sent-failed', message: 'ارسال پیامک با خطا مواجه شد.');
        }
    }

    // بقیه‌ی متدها
    #[On('delete')]

    public function delete(User $model)
    {
        $this->authorize('delete', $model);

        try {
            $model->delete();
        } catch (\Exception $e) {
        }

        return redirect()->route('admin.user.index')->with('success', 'کاربر با موفقیت حذف شد.');
    }

    public function startSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $authId = auth()->id();

        $users = User::query()
            ->when(
                (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('nutritionist'))
                || auth()->user()->roles->contains('id', nutritionRolId()),
                function ($query) use ($authId) {
                    $query->whereHas('nutritionists', function ($q) use ($authId) {
                        // Filter via pivot column to avoid self-join ambiguity
                        $q->where('nutritionist_user.nutritionist_id', $authId);
                    });
                }
            )
            ->when(isset($this->search['id']) && (int)$this->search['id'] !== 0, fn($q) => $q->where('id', $this->search['id']))
            ->when(isset($this->search['mobile']) && !empty($this->search['mobile']), fn($q) => $q->where('mobile', 'LIKE', "%{$this->search['mobile']}%"))
            ->when(isset($this->search['email']) && !empty($this->search['email']), fn($q) => $q->where('email', 'LIKE', "%{$this->search['email']}%"))
            ->when(isset($this->search['first_name']) && !empty($this->search['first_name']), function ($query) {
                return $query->whereHas('metas', function ($q) {
                    $q->where([
                        ['meta_key', UserMetaEnum::FIRST_NAME],
                        ['meta_value', 'LIKE', "%{$this->search['first_name']}%"],
                    ]);
                });
            })
            ->when(!empty($this->search['role']),
                fn($q) => $q->role($this->search['role'])
            )
            ->when(!empty($this->search['agent']),
                fn($q) => $q->whereHas('nutritionists', function ($sub) {
                    $sub->where('nutritionist_user.nutritionist_id', $this->search['agent']);
                })
            )
            ->when(!empty($this->search['sales_manager']),
                fn($q) => $q->whereHas('salesManagers', function ($sub) {
                    $sub->where('sales_manager_user.sales_manager_id', $this->search['sales_manager']);
                })
            )
            ->when(isset($this->search['last_name']) && !empty($this->search['last_name']), function ($query) {
                return $query->whereHas('metas', function ($q) {
                    $q->where([
                        ['meta_key', UserMetaEnum::LAST_NAME],
                        ['meta_value', 'LIKE', "%{$this->search['last_name']}%"],
                    ]);
                });
            })

            ->orderByDesc('id')
            ->paginate(100);

        return view('user::livewire.admin.user.user-list', compact('users'));
    }
}
