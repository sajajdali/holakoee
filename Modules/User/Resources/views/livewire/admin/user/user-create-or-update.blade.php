<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">
                {{ $isEdited ? 'ویرایش کاربر' : 'افزودن کاربر جدید' }}
            </h1>
        </div>
    </div>

    @include('admin::layouts.components.alert')

    <!-- Row -->
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <h3 class="card-title">
                        {{ $isEdited ? 'ویرایش کاربر' : 'افزودن کاربر جدید' }}
                    </h3>
                </div>
                <div class="card-body">
                    <form>
                        <!-- User basic info -->
                        <div class="form-row">
                            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 mb-3">
                                <label for="userName">نام (الزامی)</label>
                                <input type="text" class="form-control @error('userName') is-invalid @enderror"
                                       id="userName"
                                       wire:model="userName" placeholder="نام">
                                @error('userName')
                                <div id="validationuserName"
                                     class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 mb-3">
                                <label for="userLastName">نام خانوادگی (الزامی)</label>
                                <input type="text" class="form-control @error('userLastName') is-invalid @enderror"
                                       id="userLastName"
                                       wire:model="userLastName" placeholder="نام خانوادگی کاربر">
                                @error('userLastName')
                                <div id="validationuserLastName"
                                     class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <!-- Contact info -->
                        <div class="form-row">
                            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 mb-3">
                                <label for="userEmail">ایمیل (الزامی)</label>
                                <input type="text" class="form-control @error('userEmail') is-invalid @enderror"
                                       id="userEmail"
                                       wire:model="userEmail" placeholder="آدرس ایمیل">
                                @error('userEmail')
                                <div id="validationuseruserEmail"
                                     class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 mb-3">
                                <label for="userMobile">موبایل</label>
                                <input type="text" class="form-control @error('userMobile') is-invalid @enderror"
                                       id="userMobile"
                                       wire:model="userMobile" placeholder="موبایل کاربر">
                                @error('userMobile')
                                <div id="validationuseruserMobile"
                                     class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <!-- Password section -->
                        @if($user !== null)
                            <div class="form-row mb-2 mt-5">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        فقط در صورت نیاز به تغییر کلمه عبور فیلد‌های کلمه عبور و تکرار آن را پر کنید. در غیر این صورت این دو فیلد را خالی بگذارید.
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="form-row">
                            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 mb-3">
                                <label for="password">رمز عبور (الزامی)</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       id="password"
                                       wire:model="password" placeholder="رمز عبور">
                                @error('password')
                                <div id="validationuserpassword"
                                     class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 mb-3">
                                <label for="password_confirmation">تکرار رمز (الزامی)</label>
                                <input type="password"
                                       class="form-control @error('password_confirmation') is-invalid @enderror"
                                       id="password_confirmation" placeholder="تکرار رمز عبور"
                                       wire:model="password_confirmation">
                                @error('password_confirmation')
                                <div id="validationuserpassword_confirmation"
                                     class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <!-- Roles -->
                        <div class="form-row">
                            <div class="col-12">
                                <label for="password_confirmation">نقش‌ها</label>
                                @can('user')
                                    <br>
                                    نقش‌های کاربری
                                    @foreach($userRoles as $role)
                                        <div class="col-lg-12">
                                            <label class="ckbox" for="user_role_{{ $role->id }}">
                                                <input value="{{ $role->id }}" type="checkbox"
                                                       id="user_role_{{ $role->id }}"
                                                       wire:model="selectedRoles"><span>{{ $role->name }}
                                                    @if($role->hasPermissionTo('USER_DEFAULT'))
                                                        (نقش پیشفرض)
                                                    @endif
                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                    <br>
                                    نقش‌های مدیریتی
                                    @foreach($adminRoles as $role)
                                        <div class="col-lg-12">
                                            <label class="ckbox" for="user_role_{{ $role->id }}">
                                                <input value="{{ $role->id }}" type="checkbox"
                                                       id="user_role_{{ $role->id }}"
                                                       wire:model="selectedRoles"><span>{{ $role->name }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="alert alert-warning">
                                        شما فقط در نقش پیشفرض کاربری می‌توانید این کاربر را ایجاد کنید.
                                    </div>
                                @endif
                                @error('selectedRoles')
                                <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Sales Manager selection -->
                        @if(!auth()->user()->isNutritionists())
                            <div class="form-row">
                                <div class="col-12 mb-4" wire:ignore>
                                    <div class="card shadow-lg border-0" style="background: linear-gradient(135deg, #e6f7ff, #cceeff);">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="d-flex align-items-center">
                            <span class="badge bg-primary text-white me-2" style="font-size:14px;padding:8px 12px;border-radius:12px;">
                                <i class="fe fe-star"></i> انتخاب مسئول فروش
                            </span>
                                                    <small class="text-muted ms-2">
                                                        یک مسئول فروش برای این کاربر انتخاب کنید.
                                                    </small>
                                                </div>
                                                <button type="button" class="btn btn-outline-dark btn-sm" id="clear-sales-manager">
                                                    پاک کردن انتخاب
                                                </button>
                                            </div>

                                            <label for="sales-manager-select" class="mb-2 fw-bold">مسئول فروش (تک انتخاب)</label>
                                            <select class="form-control" id="sales-manager-select" data-placeholder="جستجوی مسئول فروش...">
                                                <option value="">بدون مسئول فروش</option>
                                                @foreach($salesManagers as $salesManager)
                                                    <option value="{{ $salesManager['value'] }}" @selected((string)$sales_manager_id === (string)$salesManager['value'])>
                                                        {{ $salesManager['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <div class="d-flex align-items-center mt-3" id="sales-manager-preview" style="display: none;">
                                                <div class="avatar brround me-2 d-flex align-items-center justify-content-center bg-light"
                                                     style="width:40px;height:40px;border-radius:50%;">
                                                    <i class="fa fa-user text-secondary"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold" id="sales-manager-name">—</div>
                                                    <small class="text-muted">مسئول فروش انتخابی</small>
                                                </div>
                                            </div>

                                            @error('sales_manager_id')
                                            <div class="alert alert-danger mt-3">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <!-- Agent selection -->
                        @if(!auth()->user()->isNutritionists())
                            <div class="form-row">
                            <div class="col-12 mb-4" wire:ignore>
                                <div class="card shadow-lg border-0" style="background: linear-gradient(135deg,#fff7e6,#ffeccc);">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-warning text-dark me-2" style="font-size:14px;padding:8px 12px;border-radius:12px;">
                                                    <i class="fe fe-star"></i> انتخاب نماینده
                                                </span>
                                                <small class="text-muted ms-2">
                                                    یک نماینده مسئول برای این کاربر انتخاب کنید.
                                                </small>
                                            </div>
                                            <button type="button" class="btn btn-outline-dark btn-sm" id="clear-agent">
                                                پاک کردن انتخاب
                                            </button>
                                        </div>

                                        <label for="agent-select" class="mb-2 fw-bold">نماینده (تک انتخاب)</label>
                                        <select class="form-control" id="agent-select" data-placeholder="جستجوی نماینده...">
                                            <option value="">بدون نماینده</option>
                                            @foreach($agents as $agent)
                                                <option value="{{ $agent['value'] }}" @selected((string)$agent_id === (string)$agent['value'])>
                                                    {{ $agent['label'] }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <div class="d-flex align-items-center mt-3" id="agent-preview" style="display: none;">
                                            <div class="avatar brround me-2 d-flex align-items-center justify-content-center bg-light"
                                                 style="width:40px;height:40px;border-radius:50%;">
                                                <i class="fa fa-user text-secondary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold" id="agent-name">—</div>
                                                <small class="text-muted">نماینده انتخابی</small>
                                            </div>
                                        </div>

                                        @error('agent_id')
                                        <div class="alert alert-danger mt-3">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Supporters -->
                        @if(count($supportTeam) > 0)
                            <div class="form-row">
                                <div class="col-12 mb-3" wire:ignore>
                                    <label for="supporter">
                                        پشتیبانان کاربر
                                    </label>
                                    <select multiple class="form-control" data-placeholder="انتخاب پشتیبانان"
                                            id="supporter">
                                        @foreach($supportTeam as $userSupportId => $userSupport)
                                            <option value="{{ $userSupportId }}"
                                                    @if(in_array($userSupportId,$supporter,false)) SELECTED @endif>
                                                {{ $userSupport }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-secondary mt-3 mb-5">
                                برای ایجاد پشتیبان برای این کاربر، ابتدا باید یک کاربر پشتیبانی ایجاد کنید.
                            </div>
                        @endif

                        <!-- Avatar -->
                        <div class="form-row">
                            <label for="password">تصویر کاربر</label>
                            <div class="input-group">
                               <span class="input-group-btn">
                                 <button
                                     data-for="userAvatar"
                                     data-variable="userAvatar"
                                     class="btn btn-primary select_file"
                                     data-bs-target="#file-selector-modal"
                                     data-bs-toggle="modal"
                                     type="button">
                                   <i class="fa fa-picture-o"></i>
                                     انتخاب تصویر
                                 </button>
                               </span>
                                <input id="thumbnail" class="form-control" type="text" name="filepath"
                                       wire:model="userAvatar">
                            </div>
                            <img id="holder" style="margin-top:15px;max-height:100px;" src="{{ $userAvatar }}"/>
                        </div>
                        <br>
                        <button type="button" class="btn btn-primary" wire:loading.class="bg-gray btn-loading disabled"
                                wire:click="updateOrCreate">
                            @if($user !== null)
                                ویرایش کاربر
                            @else
                                ایجاد کاربر
                            @endif
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <livewire:admin::file-manager-modal/>

</div>

@push('scripts')
    <script src="{{admin_asset('plugins/select2/select2.full.min.js')}}"></script>


    <script>
        function initSalesManagerSelect() {
            const $el = $('#sales-manager-select');

            // Destroy if already initialized
            if ($el.data('select2')) {
                $el.select2('destroy');
            }

            $el.select2({
                dir: "rtl",
                placeholder: 'جستجوی مسئول فروش...',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() { return "مسئول فروشی یافت نشد"; }
                }
            });

            // Sync changes to Livewire + preview
            $el.off('change.salesManager').on('change.salesManager', function () {
                let val = $(this).val();
                // normalize empty to null
                if (val === "" || val === undefined) val = null;
                @this.set('sales_manager_id', val);
                updateSalesManagerPreview(val);
            });

            // Default to "no sales manager" if nothing selected (null)
            @if(! $sales_manager_id)
            $el.val(null).trigger('change'); // ensures Livewire gets null
            @else
            $el.val(@json((string)$sales_manager_id)).trigger('change');
            @endif

            // Clear button: clear Select2, set Livewire to null, hide preview
            $('#clear-sales-manager').off('click.salesManager').on('click.salesManager', function () {
                $el.val(null).trigger('change');  // will call on-change handler above
                @this.set('sales_manager_id', null);      // enforce null on Livewire
                updateSalesManagerPreview(null);         // hide preview immediately
            });

            // Initial preview
            updateSalesManagerPreview($el.val() || null);
        }

        const salesManagerMap = @json(collect($salesManagers)->keyBy('value')->map(fn($a) => [
    'name' => $a['label'],
    'avatar' => $a['avatar'] ?? null
]));

        function updateSalesManagerPreview(val) {
            const box = $('#sales-manager-preview');
            if (!val || !salesManagerMap[val]) {
                box.hide();
                $('#sales-manager-preview .avatar').html('<i class="fa fa-user text-secondary"></i>');
                $('#sales-manager-name').text('—');
                return;
            }
            $('#sales-manager-name').text(salesManagerMap[val].name || '—');

            if (salesManagerMap[val].avatar) {
                $('#sales-manager-preview .avatar').html('<img src="'+salesManagerMap[val].avatar+'" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">');
            } else {
                $('#sales-manager-preview .avatar').html('<i class="fa fa-user text-secondary"></i>');
            }
            box.show();
        }

        $(document).ready(function () {
            initSalesManagerSelect();
        });

        document.addEventListener("livewire:load", () => {
            Livewire.hook('message.processed', (message, component) => {
                initSalesManagerSelect();
            });
        });

    </script>
    <script>

        function initAgentSelect() {
            const $el = $('#agent-select');

            // Destroy if already initialized
            if ($el.data('select2')) {
                $el.select2('destroy');
            }

            $el.select2({
                dir: "rtl",
                placeholder: 'جستجوی نماینده...',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() { return "نماینده‌ای یافت نشد"; }
                }
            });

            // Sync changes to Livewire + preview
            $el.off('change.agent').on('change.agent', function () {
                let val = $(this).val();
                // normalize empty to null
                if (val === "" || val === undefined) val = null;
                @this.set('agent_id', val);
                updateAgentPreview(val);
            });

            // Default to "no agent" if nothing selected (null)
            @if(! $agent_id)
            $el.val(null).trigger('change'); // ensures Livewire gets null
            @else
            $el.val(@json((string)$agent_id)).trigger('change');
            @endif

            // Clear button: clear Select2, set Livewire to null, hide preview
            $('#clear-agent').off('click.agent').on('click.agent', function () {
                $el.val(null).trigger('change');  // will call on-change handler above
                @this.set('agent_id', null);      // enforce null on Livewire
                updateAgentPreview(null);         // hide preview immediately
            });

            // Initial preview
            updateAgentPreview($el.val() || null);
        }

        // Build a simple map for preview data
        const agentMap = @json(collect($agents)->keyBy('value')->map(fn($a) => [
            'name' => $a['label'],
            'avatar' => $a['avatar'] ?? null
        ]));

        function updateAgentPreview(val) {
            const box = $('#agent-preview');
            if (!val || !agentMap[val]) {
                box.hide();
                // also reset content to icon for safety
                $('#agent-preview .avatar').html('<i class="fa fa-user text-secondary"></i>');
                $('#agent-name').text('—');
                return;
            }
            $('#agent-name').text(agentMap[val].name || '—');

            if (agentMap[val].avatar) {
                $('#agent-preview .avatar').html('<img src="'+agentMap[val].avatar+'" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">');
            } else {
                $('#agent-preview .avatar').html('<i class="fa fa-user text-secondary"></i>');
            }
            box.show();
        }

        $(document).ready(function () {
            // Supporters select2 (unchanged)
            $('#supporter').select2({
                dir: "rtl",
                placeholder: 'انتخاب پشتیبانان',
                allowClear: true,
                multiple: true,
                searchInputPlaceholder: 'جستجو',
                search: true,
                width: '100%',
            });

            $('#supporter').on('change', function () {
                var data = $('#supporter').select2("val");
                @this.set('supporter', data);
            });

            // Agent select2
            initAgentSelect();
        });

        // Re-init after Livewire DOM updates
        document.addEventListener("livewire:load", () => {
            Livewire.hook('message.processed', (message, component) => {
                initAgentSelect();
            });
        });

        // File manager (unchanged)
        Livewire.on('select_file', (param) => {
            @this.set('userAvatar', param.url);
            $('#file-selector-modal').modal('hide');
        });
    </script>

    <style>
        .select2-container { width: 100% !important; }
        .card .badge.bg-primary { box-shadow: 0 6px 20px rgba(0,123,255,.35); }
        #sales-manager-select + .select2 { box-shadow: 0 0 0 .15rem rgba(0,123,255,.25); border-radius: .5rem; }
        </style>
    <style>
        .select2-container { width: 100% !important; }
        .card .badge.bg-warning { box-shadow: 0 6px 20px rgba(255,193,7,.35); }
        #agent-select + .select2 { box-shadow: 0 0 0 .15rem rgba(255,193,7,.25); border-radius: .5rem; }
    </style>
@endpush
