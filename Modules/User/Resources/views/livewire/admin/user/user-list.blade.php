<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">مدیریت کاربران</h1>
        </div>
        <div class="ms-auto pageheader-btn d-flex gap-2">
            @can('create', \Modules\User\Entities\User::class)
                <a href="{{ route('admin.user.create') }}" class="btn btn-azure">افزودن کاربر جدید</a>
                <button type="button" class="btn btn-outline-primary" wire:click="openQuickSale">
                    فروش سریع
                </button>
            @endcan
        </div>
    </div>

    @include('admin::layouts.components.alert')

    <!-- Row -->
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom">
                    @can('user')
                        <h3 class="card-title">همه کاربران</h3>
                    @else
                        <h3 class="card-title">کاربران شما</h3>
                    @endcan
                    <div class="card-options">
                        <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                                data-bs-target="#advanceSearch" aria-expanded="false" aria-controls="advanceSearch">
                            جست و جوی پیشرفته
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="mb-5 collapse {{ $searchPanel }}" id="advanceSearch">
                        <form class="form-horizontal example">
                            <div class="row mb-4">
                                <label for="ID" class="col-md-2 form-label">ایدی</label>
                                <div class="col-md-10">
                                    <input class="form-control" id="ID" wire:model="search.id"
                                           placeholder="ایدی کاربر مورد نظر" type="text">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label for="email" class="col-md-2 form-label">ایمیل</label>
                                <div class="col-md-10">
                                    <input class="form-control" id="email" wire:model="search.email"
                                           placeholder="ایمیل کاربر مورد نظر" type="text">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label for="mobile" class="col-md-2 form-label">موبایل</label>
                                <div class="col-md-10">
                                    <input class="form-control" id="mobile" wire:model="search.mobile"
                                           placeholder="موبایل کاربر مورد نظر" type="text">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label for="first_name" class="col-md-2 form-label">نام</label>
                                <div class="col-md-10">
                                    <input class="form-control" id="first_name" wire:model="search.first_name"
                                           placeholder="نام کاربر مورد نظر" type="text">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label for="last_name" class="col-md-2 form-label">نام خانوادگی</label>
                                <div class="col-md-10">
                                    <input class="form-control" id="last_name" wire:model="search.last_name"
                                           placeholder="نام خانوادگی کاربر مورد نظر" type="text">
                                </div>
                            </div>


                            <div class="row mb-4">
                                <label for="last_name" class="col-md-2 form-label">سطح دسترسی</label>
                                <div class="col-md-10">
                                    <select wire:model="search.role" class="form-select" id="formRoles">
                                        <option value="">انتخاب کنید</option>
                                        @foreach(\Spatie\Permission\Models\Role::all() as $role)
                                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label for="formAgent" class="col-md-2 form-label">انتخاب پشتیبان</label>
                                <div class="col-md-10">
                                    <select wire:model="search.agent" class="form-select" id="formAgent">
                                        <option value="">انتخاب کنید</option>
                                        @foreach($agents as $agent)
                                            <option value="{{ $agent->id }}">{{ $agent->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label for="formAgent" class="col-md-2 form-label">انتخاب مسئول فروش</label>
                                <div class="col-md-10">
                                    <select wire:model="search.sales_manager" class="form-select" id="formAgent">
                                        <option value="">انتخاب کنید</option>
                                        @foreach($salesManagers as $salesManager)
                                            <option value="{{ $salesManager->id }}">{{ $salesManager->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>


                            <button class="btn btn-primary" type="button" wire:click="startSearch"
                                    wire:loading.class="bg-gray btn-loading disabled">
                                جست‌وجو
                            </button>
                        </form>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table text-nowrap text-md-nowrap table-bordered" wire:loading.class="op-0-3">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">نام کاربر</th>
                                <th scope="col">موبایل</th>
                                <th scope="col">نقش کاربر</th>
                                <th scope="col">مدیر فروش</th>
                                <th scope="col">پشتیبان</th>
                                <th scope="col">وضعیت</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if ($users->isNotEmpty())
                                @foreach ($users as $user)
                                    <tr wire:key="user_{{ $user->id }}">
                                        <td>
                                            @can('user.documentte', $user)
                                                <a href="{{ route('admin.user.document', $user) }}">
                                                    @endcan
                                                    {{ $user->id }}
                                                    @can('user.documentte', $user)
                                                </a>
                                            @endcan
                                        </td>
                                        <td>
                                            <div class="media mt-4 profile-footer align-items-center">
                                                <div class="media-user me-2">
                                                    <div class="main-img-user">
                                                        <img alt="{{ $user->full_name }}"
                                                             title="{{ $user->full_name }}"
                                                             class="rounded-circle avatar-md"
                                                             src="{{ $user->avatar ?? asset('assets/admin/images/svgs/user.svg') }}">
                                                    </div>
                                                </div>
                                                <div class="media-body">
                                                    @can('user.documentte', $user)
                                                        <a href="{{ route('admin.user.document', $user) }}">
                                                            <h6 class="mb-0">{{ $user->full_name }}</h6>
                                                        </a>
                                                    @else
                                                        <h6 class="mb-0 text-dark-light">{{ $user->full_name }}</h6>
                                                    @endcan
                                                </div>
                                            </div>
                                            @if($user->telegram_chat_id)
                                                <span class="badge bg-danger-gradient my-1 txt-white">
                                                    telegram code : {{ $user->telegram_chat_id }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $user->mobile }}</td>
                                        <td>
                                            @foreach ($user->roles as $index => $role)
                                                <span
                                                    class="badge bg-{{ $index === 0 ? 'info' : ($index === 1 ? 'success' : 'danger') }} my-1 text-bold">
                                                    {{ $role->name }}
                                                </span>
                                            @endforeach
                                        </td>
                                        <td class="p-2 align-middle">
                                            @php
                                                $salesManager = $user->salesManagers->first();
                                                $salesManagerName = $salesManager?->full_name;
                                                $salesManagerAvatar = $salesManager?->avatar;
                                            @endphp

                                            @if($salesManager)
                                                <div class="sales-manager-chip-rtl" @if($salesManagerName) data-bs-toggle="tooltip"
                                                     title="{{ $salesManagerName }}" @endif>
                                                    <div class="sales-manager-avatar">
                                                        @if($salesManagerAvatar)
                                                            <img src="{{ $salesManagerAvatar }}" alt="{{ $salesManagerName }}">
                                                        @else
                                                            <i class="fa fa-user"></i>
                                                        @endif
                                                        <span class="sales-manager-badge-icon">
                    <i class="fe fe-user-check"></i>
                </span>
                                                    </div>

                                                    <div class="sales-manager-meta">
                                                        <div class="sales-manager-name"
                                                             title="{{ $salesManagerName }}">{{ $salesManagerName }}</div>
                                                        <div class="sales-manager-sub">مدیر فروش</div>
                                                    </div>

                                                    <span class="sales-manager-pill">مدیر فروش</span>
                                                </div>
                                            @else
                                                <span class="sales-manager-empty">بدون مدیر فروش</span>
                                            @endif
                                        </td>
                                        <td class="p-2 align-middle">
                                            @php
                                                $sup = $user->nutritionists->first();
                                                $supName = $sup?->full_name;
                                                $supAvatar = $sup?->avatar;
                                            @endphp

                                            @if($sup)
                                                <div class="supporter-chip-rtl" @if($supName) data-bs-toggle="tooltip"
                                                     title="{{ $supName }}" @endif>
                                                    <div class="supporter-avatar">
                                                        @if($supAvatar)
                                                            <img src="{{ $supAvatar }}" alt="{{ $supName }}">
                                                        @else
                                                            <i class="fa fa-user"></i>
                                                        @endif
                                                        <span class="supporter-badge-icon">
                    <i class="fe fe-user-check"></i>
                </span>
                                                    </div>

                                                    <div class="supporter-meta">
                                                        <div class="supporter-name"
                                                             title="{{ $supName }}">{{ $supName }}</div>
                                                        <div class="supporter-sub">کارشناس تغذیه</div>
                                                    </div>

                                                    <span class="supporter-pill">پشتیبان</span>
                                                </div>
                                            @else
                                                <span class="supporter-empty">بدون نماینده</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group mt-2 mb-2">
                                                <button type="button" class="btn btn-primary dropdown-toggle"
                                                        data-bs-toggle="dropdown">
                                                    عملیات <span class="caret"></span>
                                                </button>
                                                <ul class="dropdown-menu" role="menu">
                                                    @can('update', $user)
                                                        <li>
                                                            <a href="{{ route('admin.user.edit', $user) }}">ویرایش</a>
                                                        </li>
                                                    @endcan
                                                    <li>
                                                        <a href="{{ route('admin.user.complete_registration', $user) }}">تکمیل
                                                            اطلاعات</a>
                                                    </li>
                                                    @can('update', $user)
                                                        <li>
                                                            <a href="#"
                                                               wire:click.prevent="sendSmsAgain({{ $user->id }})"
                                                               wire:confirm="آیا از ارسال مجدد پیامک اطمینان دارید؟">
                                                                ارسال مجدد لینک پیامک عضویت
                                                            </a>
                                                        </li>
                                                    @endcan

                                                    @if($user->hasRole(['پشتیبانی اپ' , 'کاربران']))
                                                        <li>
                                                            <a href="{{ route('admin.user.telegram.create', $user->id) }}">صفحه
                                                                ایجاد دسترسی به ربات تلگرام</a></li>
                                                    @endif
                                                    @can('delete', $user)
                                                        @unless ($user->id === 1)
                                                            <li><a class="delete_confirm_alert" data-label="کاربر"
                                                                   data-id="{{ $user->id }}" href="">حذف</a></li>
                                                        @else
                                                            <li><a class="admin_sweet_alert op-0-4" data-title="خطا"
                                                                   data-type="error"
                                                                   data-description="امکان حذف این کاربر وجود ندارد."
                                                                   href="#">حذف</a></li>
                                                        @endunless
                                                    @endcan
                                                </ul>
                                            </div>

                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="alert alert-info">هیچ کاربری یافت نشد</div>
                                    </td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Sale Modal --}}
    <div class="modal fade" id="quickSaleModal" tabindex="-1" aria-labelledby="quickSaleModalLabel"
         aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickSaleModalLabel">فروش سریع پکیج</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>

                <div class="modal-body">
                    @if($existingUser)
                        <div class="alert alert-warning d-flex align-items-center gap-2">
                            <i class="fe fe-alert-triangle"></i>
                            <div>کاربری با این شماره از قبل وجود دارد.</div>
                        </div>

                        @error('userExists')
                        <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                        @enderror

                        <ul class="list-group mb-3">
                            <li class="list-group-item"><strong>ID کاربر:</strong> {{ $existingUser->id }}</li>
                            <li class="list-group-item"><strong>شماره موبایل:</strong> {{ $existingUser->mobile }}</li>
                            <li class="list-group-item"><strong>نام:</strong> {{ $existingUser->full_name ?? '—' }}</li>
                            @if($existingUser->email)
                                <li class="list-group-item"><strong>ایمیل:</strong> {{ $existingUser->email }}</li>
                            @endif
                        </ul>

                        <div class="mb-3">
                            <label for="quickSalePackageId" class="form-label">حالا بگو کدام پکیج را برای این کاربر
                                می‌خوای؟</label>
                            <select id="quickSalePackageId"
                                    class="form-select @error('quickSalePackageId') is-invalid @enderror"
                                    wire:model.defer="quickSalePackageId">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($packageOptions as $pkg)
                                    <option value="{{ $pkg['id'] }}">{{ $pkg['title'] }}</option>
                                @endforeach
                            </select>
                            @error('quickSalePackageId')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    @else
                        <div class="mb-3">
                            <label for="quickSaleMobile" class="form-label">شماره موبایل</label>
                            <input type="text"
                                   id="quickSaleMobile"
                                   class="form-control @error('quickSaleMobile') is-invalid @enderror"
                                   placeholder="مثلاً 98912XXXXXXX یا با +"
                                   wire:model.defer="quickSaleMobile">
                            @error('quickSaleMobile')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="quickSalePackageId" class="form-label">انتخاب پکیج</label>
                            <select id="quickSalePackageId"
                                    class="form-select @error('quickSalePackageId') is-invalid @enderror"
                                    wire:model.defer="quickSalePackageId">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($packageOptions as $pkg)
                                    <option value="{{ $pkg['id'] }}">{{ $pkg['title'] }}</option>
                                @endforeach
                            </select>
                            @error('quickSalePackageId')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-secondary small">
                            اگر کاربری با این شماره وجود نداشته باشد به‌صورت خودکار ایجاد می‌شود و پکیج انتخابی برای او
                            ثبت می‌گردد.
                        </div>
                    @endif

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>

                    @if($existingUser)
                        <button type="button"
                                class="btn btn-primary"
                                wire:click="confirmAssignPackage"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove>ثبت بسته برای کاربر #{{ $existingUser->id }}</span>
                            <span wire:loading>در حال ثبت...</span>
                        </button>
                    @else
                        <button type="button"
                                class="btn btn-primary"
                                wire:click="saveQuickSale"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove>ثبت فروش</span>
                            <span wire:loading>در حال ثبت...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ admin_asset('plugins/sweet-alert/sweetalert.min.js') }}"></script>
    <script src="{{ admin_asset('plugins/sweet-alert/admin.sweetalert.js') }}"></script>

    <script>
        const myCollapsible = document.getElementById('advanceSearch');
        if (myCollapsible) {
            myCollapsible.addEventListener('show.bs.collapse', function () {
                @this.
                set('searchPanel', 'show');
            });
            myCollapsible.addEventListener('hide.bs.collapse', function () {
                @this.
                set('searchPanel', '');
            });
        }

        // کنترل نمایش/بستن مودال فروش سریع
        document.addEventListener('DOMContentLoaded', function () {
            const quickSaleModalEl = document.getElementById('quickSaleModal');
            let quickSaleModalInstance = null;

            Livewire.on('show-quick-sale-modal', () => {
                if (!quickSaleModalInstance) {
                    quickSaleModalInstance = new bootstrap.Modal(quickSaleModalEl, {backdrop: 'static'});
                }
                quickSaleModalInstance.show();
            });

            Livewire.on('hide-quick-sale-modal', () => {
                if (quickSaleModalInstance) quickSaleModalInstance.hide();
            });
        });

    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var nodes = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            nodes.forEach(function (el) {
                new bootstrap.Tooltip(el);
            });
        });
        document.addEventListener('DOMContentLoaded', function () {
            Livewire.on('sms-sent-success', () => {
                swal({
                    title: "انجام شد",
                    text: "پیامک مجدد با موفقیت ارسال شد.",
                    type: "success",
                    confirmButtonText: "بستن"
                });
            });

            Livewire.on('sms-sent-failed', (data) => {
                swal({
                    title: "خطا",
                    text: (data?.message ?? "ارسال پیامک ناموفق بود."),
                    type: "error",
                    confirmButtonText: "بستن"
                });
            });
        });
    </script>
    <style>
        /* container (RTL chip) for Supporter */
        .supporter-chip-rtl {
            direction: rtl;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 14px;
            background: linear-gradient(135deg, #fff8ea, #fff2d8);  /* رنگ زرد برای پشتیبان */
            border: 1px solid rgba(255, 193, 7, .35);  /* رنگ زرد برای پشتیبان */
            box-shadow: 0 6px 18px rgba(255, 193, 7, .16);  /* رنگ زرد برای پشتیبان */
            transition: transform .08s ease, box-shadow .12s ease;
            max-width: 320px;
        }

        .supporter-chip-rtl:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(255, 193, 7, .20);
        }

        /* avatar with ring + small corner badge for Supporter */
        .supporter-avatar {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            flex: 0 0 38px;
            overflow: hidden;
            background: #f5f7fb;
            color: #9aa0a6;
            border: 2px solid #fff;
            outline: 2px solid rgba(255, 193, 7, .35);  /* رنگ زرد برای پشتیبان */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .supporter-avatar i {
            font-size: 15px;
        }

        /* small icon badge on avatar corner for Supporter */
        .supporter-badge-icon {
            position: absolute;
            left: -3px;
            bottom: -3px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #fff;
            border: 1px solid rgba(255, 193, 7, .45);  /* رنگ زرد برای پشتیبان */
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d39e00;  /* رنگ زرد برای پشتیبان */
            font-size: 11px;
        }

        /* name + sub line for Supporter */
        .supporter-meta {
            min-width: 0;
        }

        .supporter-name {
            font-weight: 700;
            color: #1f2937;
            line-height: 1.1;
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .supporter-sub {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.1;
            margin-top: 2px;
        }

        /* right-side pill for Supporter */
        .supporter-pill {
            margin-right: auto;
            background: rgba(255, 193, 7, .18);  /* رنگ زرد برای پشتیبان */
            color: #8b6b00;  /* رنگ زرد برای پشتیبان */
            border: 1px solid rgba(255, 193, 7, .35);  /* رنگ زرد برای پشتیبان */
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            white-space: nowrap;
        }

        /* empty state for Supporter */
        .supporter-empty {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: #f4f6f8;
            color: #6b7280;
            border: 1px dashed #d1d5db;
            font-size: 12px;
        }
        </style>
    <style>
        /* container (RTL chip) for Sales Manager */
        .sales-manager-chip-rtl {
            direction: rtl;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 14px;
            background: linear-gradient(135deg, #e6f7ff, #cceeff);  /* رنگ آبی برای مدیر فروش */
            border: 1px solid rgba(0, 123, 255, .35);  /* رنگ آبی برای مدیر فروش */
            box-shadow: 0 6px 18px rgba(0, 123, 255, .16);  /* رنگ آبی برای مدیر فروش */
            transition: transform .08s ease, box-shadow .12s ease;
            max-width: 320px;
        }

        .sales-manager-chip-rtl:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(0, 123, 255, .20);
        }

        /* avatar with ring + small corner badge for Sales Manager */
        .sales-manager-avatar {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            flex: 0 0 38px;
            overflow: hidden;
            background: #f5f7fb;
            color: #9aa0a6;
            border: 2px solid #fff;
            outline: 2px solid rgba(0, 123, 255, .35);  /* رنگ آبی برای مدیر فروش */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sales-manager-avatar i {
            font-size: 15px;
        }

        /* small icon badge on avatar corner for Sales Manager */
        .sales-manager-badge-icon {
            position: absolute;
            left: -3px;
            bottom: -3px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #fff;
            border: 1px solid rgba(0, 123, 255, .45);  /* رنگ آبی برای مدیر فروش */
            display: flex;
            align-items: center;
            justify-content: center;
            color: #007bff;  /* رنگ آبی برای مدیر فروش */
            font-size: 11px;
        }

        /* name + sub line for Sales Manager */
        .sales-manager-meta {
            min-width: 0;
        }

        .sales-manager-name {
            font-weight: 700;
            color: #1f2937;
            line-height: 1.1;
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sales-manager-sub {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.1;
            margin-top: 2px;
        }

        /* right-side pill for Sales Manager */
        .sales-manager-pill {
            margin-right: auto;
            background: rgba(0, 123, 255, .18);  /* رنگ آبی برای مدیر فروش */
            color: #0056b3;  /* رنگ آبی برای مدیر فروش */
            border: 1px solid rgba(0, 123, 255, .35);  /* رنگ آبی برای مدیر فروش */
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            white-space: nowrap;
        }

        /* empty state for Sales Manager */
        .sales-manager-empty {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: #f4f6f8;
            color: #6b7280;
            border: 1px dashed #d1d5db;
            font-size: 12px;
        }
    </style>
@endpush
