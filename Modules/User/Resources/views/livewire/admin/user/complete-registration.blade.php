<div class="cr-wrap">

    {{-- ✅ اگر ارور وجود داشت: بالای فرم یک باکس قرمز بیاد + اسکرول بهش --}}
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-0" id="formErrorTop">
            <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
            <div>
                <div class="fw-bold">لطفاً خطاهای بالا را برطرف کنید</div>
                <div class="small opacity-75">برخی فیلدها ناقص یا نامعتبر هستند. همان‌جا قرمز شده‌اند.</div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const el = document.getElementById('formErrorTop');
                if (el) el.scrollIntoView({behavior: 'smooth', block: 'start'});
            });
        </script>
    @endif

    <form wire:submit.prevent="submit" class="d-grid gap-3">

        {{-- HERO / TOP (خوشگل‌تر) --}}
        <div class="cr-hero">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="fw-bold fs-5">تکمیل پروفایل سلامتی</div>
                    <div class="cr-sub">این اطلاعات برای طراحی برنامه اختصاصی شما استفاده می‌شود</div>
                </div>
                <span class="cr-badge text-white"
                      style="background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.25);">
                    مرحله ۱ از ۲
                </span>
            </div>

            <div class="mt-3 cr-progress">
                <span></span>
            </div>
        </div>

        {{-- اطلاعات فردی --}}
        <div class="card cr-section">
            <div class="card-body">
                <div class="cr-title"><span class="cr-dot"></span> اطلاعات فردی</div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">نام</label>
                        <input class="form-control form-control-lg @error('form.first_name') is-invalid @enderror"
                               wire:model="form.first_name">
                        @error('form.first_name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">نام خانوادگی</label>
                        <input class="form-control form-control-lg @error('form.last_name') is-invalid @enderror"
                               wire:model="form.last_name">
                        @error('form.last_name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">جنسیت</label>
                        <select class="form-select form-select-lg @error('form.gender') is-invalid @enderror"
                                wire:model="form.gender">
                            <option value="">انتخاب کنید</option>
                            @foreach($genderOptions as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('form.gender')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تاریخ تولد (شمسی)</label>

                        {{-- ✅ چون input تاریخ wire:ignore است، برای قرمز شدن دورش یک wrapper می‌گذاریم --}}
                        <div class="position-relative date-wrap @error('form.birthday.year') is-invalid-wrap @enderror"
                             wire:ignore>
                            <input
                                    id="birthdayJ"
                                    type="text"
                                    class="form-control form-control-lg"
                                    data-jdp data-jdp-only-date
                                    placeholder="مثلاً ۱۳۶۶/۰۲/۲۸"
                                    autocomplete="off"
                            >
                            <span class="position-absolute top-50 translate-middle-y"
                                  style="left:12px; opacity:.55;">
                                <i class="bi bi-calendar3"></i>
                            </span>
                        </div>

                        {{-- hidden برای نگهداری در Livewire --}}
                        <input type="hidden" wire:model="form.birthday.year">
                        <input type="hidden" wire:model="form.birthday.month">
                        <input type="hidden" wire:model="form.birthday.day">

                        @error('form.birthday.year')
                        <div class="invalid-feedback d-block">تاریخ تولد درست وارد نشده</div>
                        @enderror

                        <small class="text-muted d-block mt-1">
                            @if(($form['birthday']['year'] ?? null) && ($form['birthday']['month'] ?? null) && ($form['birthday']['day'] ?? null))
                                انتخاب شده: {{ $form['birthday']['year'] }}/{{ $form['birthday']['month'] }}/{{ $form['birthday']['day'] }}
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- هدف و سبک زندگی --}}
        <div class="card cr-section">
            <div class="card-body">
                <div class="cr-title"><span class="cr-dot"></span> هدف و سبک زندگی</div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">هدف شما</label>
                        <select class="form-select @error('form.diet_type') is-invalid @enderror"
                                wire:model="form.diet_type">
                            <option value="">انتخاب کنید</option>
                            @foreach($dietTypeOptions as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('form.diet_type')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">فعالیت روزانه</label>
                        <select class="form-select @error('form.activity_per_week') is-invalid @enderror"
                                wire:model="form.activity_per_week">
                            @foreach($activityOptions as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('form.activity_per_week')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">ورزشکار هستید؟</label>
                        <select class="form-select @error('form.athlete_or_not') is-invalid @enderror"
                                wire:model="form.athlete_or_not">
                            <option value="">انتخاب کنید</option>
                            @foreach($athleteOptions as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('form.athlete_or_not')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">میزان تغییر وزن در هفته</label>
                        <select class="form-select @error('form.weight_change_per_week') is-invalid @enderror"
                                wire:model="form.weight_change_per_week">
                            @foreach($weightChangeOptions as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('form.weight_change_per_week')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- مشخصات بدن --}}
        <div class="card cr-section">
            <div class="card-body">
                <div class="cr-title"><span class="cr-dot"></span> مشخصات بدن</div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">قد (cm)</label>
                        <input type="number"
                               class="form-control @error('form.tall') is-invalid @enderror"
                               wire:model="form.tall" min="0" max="250">
                        @error('form.tall')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">وزن اولیه (kg)</label>
                        <input type="number" step="0.1"
                               class="form-control @error('form.weight') is-invalid @enderror"
                               wire:model="form.weight" min="0" max="400">
                        @error('form.weight')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">وزن هدف (kg)</label>
                        <input type="number" step="0.1"
                               class="form-control @error('form.target_weight') is-invalid @enderror"
                               wire:model="form.target_weight" min="0" max="400">
                        @error('form.target_weight')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- پایین (خوشگل‌تر): محدودیت + حساسیت + بیماری‌ها --}}
        <div class="card cr-section">
            <div class="card-body">
                <div class="cr-title"><span class="cr-dot"></span> محدودیت، حساسیت و بیماری</div>

                <div class="mb-2 text-muted small">محدودیت غذایی</div>
                <div id="checkbox_cover">
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        @foreach($foodRestrictionOptions as $k => $v)
                            <label class="pill @if(in_array($k, $form['food_restriction'] ?? [])) is-active @endif">
                                <input class="form-check-input" type="checkbox" value="{{ $k }}"
                                       wire:model="form.food_restriction">
                                <span>{{ $v }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mb-2 text-muted small">حساسیت غذایی</div>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        @foreach($foodAllergyOptions as $k => $v)
                            <label class="pill @if(in_array($k, $form['food_allergy'] ?? [])) is-active @endif">
                                <input class="form-check-input" type="checkbox" value="{{ $k }}"
                                       wire:model="form.food_allergy">
                                <span>{{ $v }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mb-2 text-muted small">بیماری‌ها (از سرور)</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($diseaseOptions as $k => $v)
                            <label class="pill @if(in_array($k, $form['diseases'] ?? [])) is-active @endif">
                                <input class="form-check-input" type="checkbox" value="{{ $k }}"
                                       wire:model="form.diseases">
                                <span>{{ $v }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- تجویز رژیم --}}
        <div class="card cr-section">
            <div class="card-body">
                <div class="cr-title">
                    <span class="cr-dot"></span>
                    تجویز رژیم
                </div>

                <label class="assign-diet-toggle">
                    <input type="checkbox" wire:model.live="assignDietEnabled">
                    <span class="text">آیا می‌خواهید برای کاربر رژیم هم تجویز کنید؟</span>
                </label>

                <div class="text-muted small mt-1">
                    در صورت فعال‌سازی، امکان انتخاب و ارسال رژیم فراهم می‌شود.
                </div>

                @if($assignDietEnabled)
                    <hr>
                    @if(!$hasActivePackage)
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-0">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            <div>
                                <div class="fw-bold">کاربر بسته فعال ندارد</div>
                                <div class="small opacity-75">
                                    برای تجویز رژیم یا فعال‌سازی خدمات، ابتدا باید بسته‌ای برای کاربر فعال شود.
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-0">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <div>
                                <div class="fw-bold">بسته کاربر فعال است</div>
                                <div class="small">
                                    تاریخ اتمام بسته:
                                    <span class="fw-semibold">{{ $packageExpireAt }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                    <hr>

                    {{-- انتخاب رژیم --}}
                    <div class="form-group mt-3">
                        <label class="form-label mb-2">انتخاب رژیم</label>
                        <select class="form-select @error('selectedDiet') is-invalid @enderror"
                                wire:model="selectedDiet">
                            <option value="">انتخاب کنید</option>
                            @foreach(\Modules\Diet\Entities\DietPlan::where('general_pattern',0)->get() as $diet)
                                <option value="{{ $diet->id }}">{{ $diet->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedDiet')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        @if($suggestedDiet)
                            <small class="text-muted d-block mt-1">
                                پلن پیشنهادی به‌صورت پیش‌فرض انتخاب شده است
                            </small>
                        @endif
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" wire:model="sendSmsAfterSendDiet" id="sendSms">
                        <label class="form-check-label" for="sendSms">
                            ارسال پیامک پس از تجویز رژیم
                        </label>
                    </div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="card cr-section">
            <div class="cr-footer d-flex align-items-center justify-content-between gap-2">
                <div class="text-muted small">
                    با ذخیره کردن، اطلاعات به شکل استاندارد ثبت می‌شود.
                    <span wire:loading class="ms-2">در حال پردازش…</span>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    {{-- ✅ متن کنار دکمه اگر خطا باشد --}}
                    @if($errors->any())
                        <span class="text-danger small fw-semibold">
                            لطفاً ارورهای بالا را برطرف کنید
                        </span>
                    @endif

                    <button type="submit"
                            class="btn btn-primary"
                            style="background: linear-gradient(135deg,#2563eb,#06b6d4); border:0"
                            wire:loading.attr="disabled">
                        ذخیره و ادامه
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

@push('scripts')
    {{-- select2 (اگر استفاده نمی‌کنی می‌تونی پاکش کنی) --}}
    <script src="{{ admin_asset('plugins/select2/select2.full.min.js') }}"></script>

    <script src="{{ asset('assets/admin/jalali/jalalidatepicker.min.js') }}"></script>

    <script>
        document.addEventListener('livewire:init', () => {

            // فقط یک بار datepicker رو فعال کن
            if (!window.__jdp_inited) {
                window.__jdp_inited = true;
                jalaliDatepicker.startWatch({
                    minDate: "attr",
                    maxDate: "attr",
                    zIndex: 99999
                });
            }

            const el = document.getElementById('birthdayJ');
            if (!el) return;

            const toEnDigits = (str) => {
                if (!str) return str;
                const fa = '۰۱۲۳۴۵۶۷۸۹';
                const ar = '٠١٢٣٤٥٦٧٨٩';
                return String(str)
                    .split('')
                    .map(ch => {
                        let i = fa.indexOf(ch);
                        if (i > -1) return String(i);
                        i = ar.indexOf(ch);
                        if (i > -1) return String(i);
                        return ch;
                    })
                    .join('');
            };

            // مقدار اولیه از form به input نمایش بده
            const y = @json($form['birthday']['year'] ?? null);
            const m = @json($form['birthday']['month'] ?? null);
            const d = @json($form['birthday']['day'] ?? null);

            if (y && m && d) {
                const mm = String(m).padStart(2, '0');
                const dd = String(d).padStart(2, '0');
                el.value = `${y}/${mm}/${dd}`;
            }

            // هر وقت کاربر تاریخ انتخاب کرد => sync با Livewire
            const syncBirthday = () => {
                const val = toEnDigits((el.value || '').trim());
                if (!val) return;

                const parts = val.split('/');
                if (parts.length !== 3) return;

                const year = Number(parts[0]);
                const month = Number(parts[1]);
                const day = Number(parts[2]);

                if (!year || !month || !day) return;

                @this.set('form.birthday.year', year);
                @this.set('form.birthday.month', month);
                @this.set('form.birthday.day', day);
            };

            el.addEventListener('change', syncBirthday);
            el.addEventListener('input', syncBirthday);

            // ✅ اگر خطا داشت، اولین فیلد invalid رو فوکوس کن
            document.addEventListener('livewire:navigated', () => {
                const firstInvalid = document.querySelector('.is-invalid, .is-invalid-wrap');
                if (firstInvalid) firstInvalid.scrollIntoView({behavior: 'smooth', block: 'center'});
            });

            // برای submit های معمولی هم:
            window.addEventListener('focus-first-invalid', () => {
                const firstInvalid = document.querySelector('.is-invalid, .is-invalid-wrap');
                if (firstInvalid) firstInvalid.scrollIntoView({behavior: 'smooth', block: 'center'});
            });
        });
    </script>
@endpush

@push('styles')

    <style>
        .cr-wrap { direction: rtl; }

        /* ✅ قرمز کردن input تاریخ که wire:ignore هست */
        .is-invalid-wrap input {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 .25rem rgba(220,53,69,.15) !important;
        }

        .cr-hero {
            border-radius: 18px;
            padding: 18px 18px 14px;
            color: #fff;
            background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(2, 132, 199, .18);
        }
        .cr-hero:before {
            content: "";
            position: absolute;
            inset: -40px -80px auto auto;
            width: 240px;
            height: 240px;
            background: rgba(255, 255, 255, .14);
            border-radius: 999px;
            transform: rotate(18deg);
        }
        .cr-hero .cr-sub { opacity: .85; font-size: .9rem; }

        .cr-progress {
            height: 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .25);
            overflow: hidden;
        }
        .cr-progress > span {
            display: block;
            height: 100%;
            width: 68%;
            background: rgba(255, 255, 255, .95);
            border-radius: 999px;
        }

        .cr-section {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
        }
        .cr-section .card-body { padding: 18px; }

        .cr-title {
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cr-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            box-shadow: 0 0 0 6px rgba(37, 99, 235, .12);
        }

        /* Pills */
        .pill {
            border: 1px solid #e5e7eb;
            background: #fff;
            border-radius: 999px;
            margin-right: 2px;
            padding: 10px 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
            transition: all .15s ease;
        }
        #checkbox_cover span { margin-right: 20px !important; }
        .pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(2, 6, 23, .06);
        }
        .pill input { margin: 0; }
        .pill.is-active {
            border-color: rgba(37, 99, 235, .45);
            background: rgba(37, 99, 235, .06);
        }

        /* Sticky footer */
        .cr-footer {
            position: sticky;
            bottom: 0;
            z-index: 20;
            background: rgba(255, 255, 255, .9);
            backdrop-filter: blur(10px);
            border-top: 1px solid #eef2ff;
            padding: 12px;
            border-radius: 14px;
            box-shadow: 0 -10px 30px rgba(15, 23, 42, .06);
        }
        .cr-footer .btn {
            border-radius: 14px;
            padding: 12px 18px;
            font-weight: 700;
        }

        .cr-badge {
            font-size: .82rem;
            padding: 8px 10px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #0f172a;
        }
    </style>
@endpush
