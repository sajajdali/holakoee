<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">گزارش تأیید رژیم‌ها</h1>
            <p class="text-muted mb-0">آمار تأیید کارشناسی رژیم‌ها به تفکیک مدیر و کارشناس</p>
        </div>
        <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.presCribed-diets') }}" class="btn btn-outline-primary">
                بازگشت به رژیم‌های تجویز شده
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h3 class="card-title">فیلتر گزارش</h3>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="approval-report-approver" class="form-label">مدیر / کارشناس تأییدکننده</label>
                    <select id="approval-report-approver" class="form-select" wire:model="search.approver_id">
                        <option value="">همه مدیران و کارشناسان</option>
                        @foreach($availableApprovers as $approverOption)
                            <option value="{{ $approverOption->id }}">
                                @if($approverOption->user)
                                    {{ $approverOption->user->full_name ?: $approverOption->user->mobile }}
                                    — {{ $approverOption->user->roles->pluck('name')->implode('، ') }}
                                @else
                                    کاربر حذف‌شده #{{ $approverOption->id }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label for="approval-report-from-date" class="form-label">از تاریخ تأیید</label>
                    <div wire:ignore>
                        <input
                            id="approval-report-from-date"
                            type="text"
                            class="form-control"
                            value="{{ $dateSearch['fromDate'] ?? '' }}"
                            data-jdp
                            data-jdp-only-date
                            placeholder="مثلاً ۱۴۰۵/۰۱/۰۱"
                            autocomplete="off"
                        >
                    </div>
                    @error('dateSearch.fromDate')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-3">
                    <label for="approval-report-to-date" class="form-label">تا تاریخ تأیید</label>
                    <div wire:ignore>
                        <input
                            id="approval-report-to-date"
                            type="text"
                            class="form-control"
                            value="{{ $dateSearch['toDate'] ?? '' }}"
                            data-jdp
                            data-jdp-only-date
                            placeholder="مثلاً ۱۴۰۵/۰۱/۳۱"
                            autocomplete="off"
                        >
                    </div>
                    @error('dateSearch.toDate')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="button" class="btn btn-primary flex-fill" wire:click="startSearch"
                            wire:loading.attr="disabled">
                        نمایش گزارش
                    </button>
                    @if(filled($search['approver_id'] ?? null) || filled($search['fromDate'] ?? null) || filled($search['toDate'] ?? null))
                        <button type="button" class="btn btn-secondary" wire:click="resetFilters"
                                wire:loading.attr="disabled">
                            حذف فیلتر
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row row-sm mb-4">
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <span class="avatar avatar-lg bg-primary-transparent text-primary me-3">
                        <i class="fe fe-check-circle fs-24"></i>
                    </span>
                    <div>
                        <div class="text-muted">کل رژیم‌های تأییدشده</div>
                        <div class="fs-24 fw-bold">{{ number_format($totals['approved']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h3 class="card-title">آمار به تفکیک مدیر و کارشناس</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle mb-0" wire:loading.class="op-0-3">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>نام تأییدکننده</th>
                        <th>سمت</th>
                        <th>تعداد تأیید</th>
                        <th>آخرین تأیید</th>
                        <th>مشاهده ریز</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($summary as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                {{ $row->user?->full_name ?: ($row->user?->mobile ?? 'کاربر حذف‌شده #' . $row->approver_id) }}
                            </td>
                            <td>{{ $row->user?->roles?->pluck('name')->implode('، ') ?: '-' }}</td>
                            <td><span class="badge bg-primary">{{ number_format($row->total_approved) }}</span></td>
                            <td>
                                @if($row->last_approved_at)
                                    {{ verta($row->last_approved_at)->format(
                                        strlen($row->last_approved_at) > 10 ? 'Y/m/d ساعت H:i' : 'Y/m/d'
                                    ) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        wire:click="showApprover({{ $row->approver_id }})">
                                    نمایش رژیم‌ها
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="alert alert-info mb-0">موردی در این بازه یافت نشد.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <h3 class="card-title">ریز رژیم‌های تأییدشده</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive mb-3">
                <table class="table table-bordered text-center align-middle text-nowrap mb-0" wire:loading.class="op-0-3">
                    <thead>
                    <tr>
                        <th>شناسه رژیم</th>
                        <th>تأییدکننده</th>
                        <th>کاربر</th>
                        <th>پلن رژیم</th>
                        <th>تاریخ و ساعت تأیید</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($diets as $diet)
                        <tr>
                            <td>{{ $diet->id }}</td>
                            <td>
                                {{ $diet->expertApprover?->full_name ?: ($diet->expertApprover?->mobile ?? 'کاربر حذف‌شده') }}
                            </td>
                            <td>
                                @if($diet->user)
                                    <a href="{{ route('admin.user.document', $diet->user) }}">
                                        {{ $diet->user->full_name ?: $diet->user->mobile }}
                                    </a>
                                @else
                                    <span class="text-danger">کاربر حذف‌شده</span>
                                @endif
                            </td>
                            <td>{{ $diet->dietPlan?->name ?? '-' }}</td>
                            <td>
                                @if($diet->expert_approved_at)
                                    {{ verta($diet->expert_approved_at)->format('Y/m/d') }}
                                    @if($diet->expert_approved_at->format('H:i:s') === '00:00:00')
                                        <small class="text-muted">(ساعت در داده قدیمی ثبت نشده)</small>
                                    @else
                                        ساعت {{ verta($diet->expert_approved_at)->format('H:i') }}
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.presCribed-diets.detail', $diet) }}"
                                   class="btn btn-sm btn-primary">
                                    مشاهده رژیم
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="alert alert-info mb-0">رژیم تأییدشده‌ای در این بازه وجود ندارد.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $diets->links() }}
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            if (!window.__jdp_inited) {
                window.__jdp_inited = true;
                jalaliDatepicker.startWatch({
                    minDate: 'attr',
                    maxDate: 'attr',
                    zIndex: 99999
                });
            }

            function syncJalaliInput(id, property) {
                const input = document.getElementById(id);
                if (!input || input.dataset.livewireDateBound === '1') {
                    return;
                }

                input.dataset.livewireDateBound = '1';

                const sync = () => @this.set(property, input.value);
                input.addEventListener('change', sync);
                input.addEventListener('input', sync);
            }

            syncJalaliInput('approval-report-from-date', 'dateSearch.fromDate');
            syncJalaliInput('approval-report-to-date', 'dateSearch.toDate');

            Livewire.on('approval-report-dates-reset', () => {
                document.getElementById('approval-report-from-date').value = '';
                document.getElementById('approval-report-to-date').value = '';
            });
        });
    </script>
@endpush
