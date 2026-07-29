<div>
    <div class="row mt-4" id="rejims">
        <div class="card pt-3">
            <div class="col-lg-12">
                @if (!empty($this->details))
                    @if(isset($successFully))
                        <div class="alert alert-success mt-4 alert-dismissible fade show" role="alert">
                            <span class="alert-inner--text">{{$successFully}}</span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                    @endif
                    @if($needExpertApproved && isset($dietReqeust->dietPlan->detail['need_expert_approval']))
                        <div class="alert alert-secondary" role="alert">
                            <span class="alert-inner--icon me-2"><i class="fe fe-info"></i></span>
                            <span class="alert-inner--text">این رژیم نیازمند تایید توسط شماست و تا زمان عدم تایید برای کاربر ارسال نمیشود
                            </span>
                        </div>
                    @endif
                    @if($dietReqeust->status == \Modules\Diet\Enum\DietRequestStatusEnum::PENDING && $dietReqeust->scheduled_at->isFuture())
                        <div class="alert alert-warning" role="alert">
                            <span class="alert-inner--icon me-2"><i class="fe fe-info"></i></span>

                            <span class="alert-inner--text">زمان ارسال رژیم فرا نرسیده است .
                            <br>
                                این رژیم در تاریخ
                                 {{ verta($dietReqeust->scheduled_at)->format('Y/m/d ساعت H:i') }} دقیقه
                                برای کاربر به صورت اتوماتیک ارسال خواهد شد.
                                <br>
                                  @if(isset($dietReqeust->dietPlan->detail['need_expert_approval']))
                                    <br>
                                    نکته :   بعد از تایید توسط شما در این زمان ارسال میشود
                                @endif
                            </span>
                        </div>
                    @endif
                    @if(isset($dietReqeust->detail['sms']['diet_finished_sent_at']))
                        <div class="alert alert-success" role="alert">
                            پیامک وارد کردن وزن جدید برای این کاربر در تاریخ
                            {{getPersianDateFull($dietReqeust->detail['sms']['diet_finished_sent_at'])}}
                            ارسال شده است
                        </div>
                    @endif
                    @if($dietReqeust->send_at)
                        <div class="alert alert-info" role="alert">
                            <span class="alert-inner--icon me-2"><i class="fe fe-info"></i></span>
                            <span class="alert-inner--text"> این رژیم
                                    @if($dietReqeust->sender_id)
                                    توسط :
                                    {{$dietReqeust->sender?->full_name ?? '-' }} و به صورت دستی ،
                                @else
                                    توسط خود سیستم به صورت اتوماتیک
                                @endif
                            <br>
                                در تاریخ
                                 {{ verta($dietReqeust->send_at)->format('Y/m/d ساعت H:i') }} دقیقه
                                 برای کاربر ارسال شده است
                                <br>
                            </span>
                        </div>
                    @endif
                    <h4 class="mb-5 mt-3">رژیم ثبت شده</h4>
                    <table class="table text-nowrap text-md-nowrap table-bordered text-center"
                           wire:loading.class="op-0-3">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">نام رژیم</th>
                            <th scope="col">وضعیت</th>
                            <th scope="col">میزان کالری</th>
                            <th scope="col">تاریخ شروع</th>
                            <th scope="col">تاریخ اتمام</th>
                            <th scope="col">تاریخ درخواست</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if ($dietReqeust->exists())
                            <tr>
                                <td class="text-center">{{ $dietReqeust->id }}</td>
                                <td class="text-center">{!! $dietReqeust->dietPlan?->name ?? ' <span class="rounded-pill bg-warning p-1">یافت نشد</span>' !!} </td>
                                <td>
                                    @if($needExpertApproved && isset($dietReqeust->dietPlan->detail['need_expert_approval']))
                                        <span class=" tag tag-light">تا زمانی که تایید نکنید رژیم ارسال نمیشود</span>
                                        <br>
                                        <button type="button"
                                                class="btn btn-sm btn-success"
                                                wire:click="confirmByExpert"
                                                wire:confirm="غذاهای لیست رژیم را تایید میکنید؟"
                                                wire:loading.class="btn-loading"
                                                wire:loading.attr="disabled">
                                            تایید لیست غذاها
                                        </button>
                                    @else
                                        {!! $dietReqeust->status->getName() !!}
                                        @if($dietReqeust->status == \Modules\Diet\Enum\DietRequestStatusEnum::PENDING)
                                            <br>
                                            <br>
                                            <button type="button"
                                                    class="btn btn-sm btn-cyan"
                                                    wire:click="confirmAndSendDiet"
                                                    wire:confirm="با انتخاب این گزینه رژیم تایید و برای کاربر ارسال میشود و یک پیامک هم برای وی ارسال میشود"
                                                    wire:loading.class="btn-loading"
                                                    wire:loading.attr="disabled">
                                                تایید و ارسال
                                            </button>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    {{ number_format($dietReqeust->calories) }}
                                    @if(!$changeCalorie)
                                        <button type="button"
                                                class="btn btn-sm btn-cyan"
                                                wire:click="toggleChangeCalorie"
                                                wire:loading.class="btn-loading"
                                                wire:loading.attr="disabled">
                                            تغییر
                                        </button>
                                        <br>
                                        <br>
                                        <span
                                                class=" tag tag-light"> کالری پایه :{{number_format($dietReqeust->detail['caloriesAndUnits']['base_calorie'])}}</span>
                                    @else
                                        <div class="alert alert-info mt-3">
                                            <div class="form-group">
                                                <label class="form-label" for="default-dropdown">کالری مورد نظر
                                                    شما</label>
                                                <input type="number"
                                                       class="form-control @error('form.new_calorie') is-invalid @enderror"
                                                       id="fasting_name"
                                                       wire:model="form.new_calorie" placeholder="کالری جدید">
                                                @error('form.new_calorie')
                                                <div style="display: block"
                                                     class="invalid-feedback">   {{ $message }}  </div> @enderror
                                            </div>
                                            <div class="form-group">
                                                <button type="button"
                                                        class="btn btn-success "
                                                        wire:click="changeCalorieAction"
                                                        wire:loading.class="btn-loading"
                                                        wire:loading.attr="disabled"
                                                >
                                                    تغییر کالری
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    {{ verta($dietReqeust->start_date)->format('Y/m/d') }}
                                    <button type="button"
                                            class="btn btn-sm btn-cyan"
                                            wire:click="openEditDatesModal"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="btn-loading">
                                        ویرایش تاریخ شروع/پایان
                                    </button>
                                </td>
                                <td>
                                    {{ verta($dietReqeust->end_date)->format('Y/m/d') }}
                                </td>
                                <td>
                                    {{ verta($dietReqeust->created_at)->format('Y/m/d ساعت H:i') }}
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>

                    <div class="col-md-12 mt-5">
                        <div class="card-header flex-column align-items-start">
                            <h5>وضعیت پیشرفت رژیم</h5>
                            <hr class="w-100">
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="chart-container">
                                        <canvas id="chartPie" class="h-275"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h4 class="mb-5 mt-3">جزئیات آمار</h4>
                                    {{-- <div class="vr h-75" style="opacity:0.1"></div> --}}
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table border text-nowrap text-md-nowrap table-striped">

                                                <tbody>
                                                <tr>
                                                    <td>طول دوره</td>
                                                    <td>{{ $totaldays }} دوره</td>
                                                </tr>
                                                <tr>
                                                    <td>تعداد وعده ها</td>
                                                    <td>{{ $totallMeals }}</td>
                                                </tr>
                                                <tr>
                                                    <td>درصد وعده های مصرف شده</td>
                                                    <td>{{ $donedlMeals }}</td>
                                                </tr>
                                                {{--                                                <tr>--}}
                                                {{--                                                    <td>درصد وعده های مانده</td>--}}
                                                {{--                                                    <td>{{ $leftToConsume }}</td>--}}
                                                {{--                                                </tr>--}}

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if (array_key_exists('handwritten', $dietReqeust->detail))
                        <hr class="mt-5 mb-3" style="opacity: 0.5">
                        <h4 class="mt-4 mb-5">یادداشت های مربط با وعده ها</h4>
                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap text-md-nowrap table-bordered text-center"
                                   wire:loading.class="op-0-3">
                                <thead>
                                <tr class="text-center">
                                    <th scope="col">وعده</th>
                                    <th scope="col">عنوان</th>
                                    <th scope="col">توضیحات</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($dietReqeust->detail['handwritten'] as $meal_id => $handWritten)
                                    <tr>
                                        <td>{{ Modules\Diet\Entities\Meal::find($meal_id)?->name }}</td>
                                        <td>{{ $handWritten['title'] }}</td>
                                        <td>{{ $handWritten['body'] }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <hr class="mt-5 mb-3" style="opacity: 0.5">
                    <h4 class="mt-4 mb-5">مشخصات تغذیه ای هر وعده</h4>
                    <div class="card">
                        <div class="wideget-user-tab">
                            <div class="tab-menu-heading">
                                <div class="tabs-menu1" wire:ignore>
                                    <ul class="nav">
                                        @foreach ($this->details as $key => $detailItems)
                                            <li><a href="#rejimDetail_{{ $key }}"
                                                   class="@if ($loop->first) active show @endif"
                                                   data-bs-toggle="tab">
                                                    {{ Modules\Diet\Enum\MainNutritionEnum::tryFrom($key)->getName() }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-content">

                        @foreach ($this->allDetails as $key => $detailItems)
                            <div wire:ignore.self class="tab-pane @if ($loop->first) active show @endif"
                                 id="{{ 'rejimDetail_'.$key }}">
                                <div class="card">
                                    <div class="card-body p-0">
                                        <div class="row row-sm">
                                            <div class="col-lg-12">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <div class="mb-5 collapse" id="advanceSearch"></div>

                                                        <div class="table-responsive mb-3">
                                                            <table
                                                                    class="table text-nowrap text-md-nowrap table-bordered"
                                                                    wire:loading.class="op-0-3">
                                                                <thead>
                                                                <tr class="text-center">
                                                                    <th>#</th>
                                                                    <th>روز</th>
                                                                    <th>تاریخ روز</th>
                                                                    <th>وعده</th>
                                                                    <th>نام وعده غذایی</th>
                                                                    <th>P</th>
                                                                    <th>C</th>
                                                                    <th>FAT</th>
                                                                    <th>Fib</th>
                                                                    <th>Cal</th>
                                                                    <th>وضعیت</th>
                                                                    <th>جایگزین</th>
                                                                </tr>
                                                                </thead>

                                                                @php
                                                                    $currentDay = null;
                                                                    $totalProtein = 0;
                                                                    $totalCarb = 0;
                                                                    $totalFat = 0;
                                                                    $totalFiber = 0;
                                                                    $totalCalories = 0;
                                                                @endphp

                                                                <tbody>
                                                                @if ($detailItems && $detailItems->count())
                                                                    @foreach ($detailItems as $index => $detail)
                                                                        @if ($currentDay !== $detail->day_number)
                                                                            @if ($currentDay !== null)
                                                                                <tr class="fw-bold bg-light">
                                                                                    <td colspan="5" class="text-center">
                                                                                        جمع روز {{ $currentDay }}</td>
                                                                                    <td>{{ number_format($totalProtein) }}</td>
                                                                                    <td>{{ number_format($totalCarb) }}</td>
                                                                                    <td>{{ number_format($totalFat) }}</td>
                                                                                    <td>{{ number_format($totalFiber) }}</td>
                                                                                    <td>{{ number_format($totalCalories) }}</td>
                                                                                    <td></td>
                                                                                    <td></td>
                                                                                </tr>

                                                                                @php
                                                                                    $totalProtein = 0;
                                                                                    $totalCarb = 0;
                                                                                    $totalFat = 0;
                                                                                    $totalFiber = 0;
                                                                                    $totalCalories = 0;
                                                                                @endphp
                                                                            @endif

                                                                            @php $currentDay = $detail->day_number; @endphp
                                                                        @endif

                                                                        @php
                                                                            if ($detail->replaced_parent_id == null) {
                                                                                $totalProtein += $detail->protein;
                                                                                $totalCarb += $detail->carb;
                                                                                $totalFat += $detail->fat;
                                                                                $totalFiber += $detail->fiber;
                                                                                $totalCalories += $detail->calories;
                                                                            }
                                                                        @endphp

                                                                        <tr wire:key="detail-{{ $detail->id }}"
                                                                            @if(! $this->isReplaced($detail))
                                                                                style="background-color: {{ $color[$this->caculateColorAndParent($detail) - 1] }};"
                                                                            @else
                                                                                style="opacity: 0.3;"
                                                                                @endif>
                                                                            <td class="text-center">{{ $detail->id }}</td>
                                                                            <td class="text-center">{{ $detail->day_number }}</td>
                                                                            <td>{{ verta($detail->date_of_day)->format('Y/m/d') }}</td>
                                                                            <td>
                                                                                @if(isset($detail->foodable->id))
                                                                                    <a href="{{ route('admin.food.edit', $detail->foodable->id) }}">
                                                                                        {{ app(\Modules\Diet\Service\DietService::class)->handleMealName($dietReqeust, $detail->meal) }}
                                                                                    </a>
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                @if ($foodTypeIsComined)
                                                                                    @foreach ($this->complexFoodResult($detail) as $items)
                                                                                        {{ $items }} <br>
                                                                                    @endforeach
                                                                                @else
                                                                                    @unless($key == \Modules\Diet\Enum\MainNutritionEnum::special->value)
                                                                                        {{ $detail->number_of_unit }} {{ $detail->foodClass->foodUnit?->name ?? '-' }}
                                                                                    @endunless
                                                                                    {{ $detail->foodClass?->name ?? '-' }}
                                                                                @endif

                                                                                @php($dietDetail = json_decode($detail->detail , true))
                                                                                @if(isset($dietDetail['replaced_from']) && $dietDetail['replaced_from'] != "")
                                                                                    @php($replacerUser = \Modules\User\Entities\User::find($dietDetail['replaced_from']))
                                                                                    <span
                                                                                            class="badge badge-sm bg-secondary badge-hide">
                                                                                        جایگزین شده توسط
                                                                                        {{ $replacerUser ? $replacerUser->full_name : 'کاربر حذف شده' }}
                                                                                    </span>
                                                                                @endif
                                                                            </td>

                                                                            <td>{{ $detail->protein }}</td>
                                                                            <td>{{ $detail->carb }}</td>
                                                                            <td>{{ $detail->fat }}</td>
                                                                            <td>{{ $detail->fiber }}</td>
                                                                            <td>{{ $detail->calories }}</td>
                                                                            <td class="text-center">
                                                                                {!! $detail->is_done != 0
                                                                                    ? '<i class="fa fa-check text-success fs-5" data-bs-toggle="tooltip" title="صرف شده"></i>'
                                                                                    : '<i class="fa fa-minus text-muted fs-5" data-bs-toggle="tooltip" title="صرف نشده"></i>' !!}
                                                                            </td>

                                                                            {{-- ستون جایگزین --}}
                                                                            <td class="text-center">
                                                                                @if(! $this->isReplaced($detail))
                                                                                    <button
                                                                                            type="button"
                                                                                            class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center gap-1 mx-auto"
                                                                                            wire:click="selectDetailForReplace({{ $detail->id }})"
                                                                                            data-bs-toggle="modal"
                                                                                            data-bs-target="#replaceMealModal"
                                                                                            title="جایگزین کردن وعده">
                                                                                        🔄
                                                                                    </button>
                                                                                @else
                                                                                    <span
                                                                                            class="badge text-black badge-sm bg-warning badge-hide">جایگزین</span>
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach

                                                                    {{-- جمع روز آخر --}}
                                                                    <tr class="fw-bold bg-light">
                                                                        <td colspan="5" class="text-center">جمع
                                                                            روز {{ $currentDay }}</td>
                                                                        <td>{{ number_format($totalProtein) }}</td>
                                                                        <td>{{ number_format($totalCarb) }}</td>
                                                                        <td>{{ number_format($totalFat) }}</td>
                                                                        <td>{{ number_format($totalFiber) }}</td>
                                                                        <td>{{ number_format($totalCalories) }}</td>
                                                                        <td></td>
                                                                        <td></td>
                                                                    </tr>
                                                                @else
                                                                    <tr>
                                                                        <td colspan="12" class="text-center">
                                                                            <div class="alert alert-info">خطا در نمایش
                                                                                جزئیات!
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                                </tbody>

                                                            </table>
                                                        </div> <!-- .table-responsive -->

                                                        <div></div>
                                                    </div> <!-- .card-body -->
                                                </div> <!-- .card -->
                                            </div> <!-- .col-lg-12 -->
                                        </div> <!-- .row -->
                                    </div> <!-- .card-body p-0 -->
                                </div> <!-- .card -->
                            </div> <!-- .tab-pane -->
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal: replace meal -->
    <div wire:ignore.self class="modal fade" id="replaceMealModal" tabindex="-1" aria-labelledby="replaceMealModalLabel"
         aria-hidden="true" x-data="{
        searchTerm: '',
        replaceItems: @entangle('replaceItems'),
        searchFilteredItems() {
            return this.replaceItems.filter(item => item.name.toLowerCase().includes(this.searchTerm.toLowerCase()));
        }
     }">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="replaceMealModalLabel">جایگزین کردن وعده</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>

                <div class="modal-body">
                    @if($selectedDetail)
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <span class="ms-2">🔄 </span>
                            <div>
                                در حال جایگزین کردن
                                <strong>{{ $selectedDetail->foodClass?->name ?? '-' }}</strong>
                                برای وعده‌ی
                                <strong>{{ $selectedDetail->meal?->name ?? '-' }}</strong>
                                در روز
                                <strong>{{ $selectedDetail->day_number }}</strong>
                                ({{ verta($selectedDetail->date_of_day)->format('Y/m/d') }}) هستید
                            </div>
                        </div>
                    @endif

                    <!-- فیلد جستجو -->
                    <div class="mb-3">
                        <input type="text"
                               x-model="searchTerm"
                               class="form-control"
                               placeholder="جستجو در لیست جایگزین‌ها..."/>
                    </div>

                    {{-- لودینگ هنگام انتخاب ردیف --}}
                    <div class="text-center py-5" wire:loading.flex wire:target="selectDetailForReplace">
                        <div class="spinner-border" role="status" aria-hidden="true"></div>
                        <p class="mt-3 mb-0">در حال بارگذاری لیست جایگزین‌ها...</p>
                    </div>

                    {{-- محتوا بعد از لود --}}
                    <div wire:loading.remove wire:target="selectDetailForReplace">
                        @if($replaceStatus === false)
                            <div class="alert alert-warning d-flex align-items-center" role="alert">
                                <span class="ms-2">⚠️</span>
                                <div>{{ $replaceMessage ?? 'غذایی برای جایگزینی یافت نشد.' }}</div>
                            </div>
                        @elseif($replaceStatus === true && count($replaceItems))
                            <div class="list-group" id="replaceItemsList">
                                <template x-for="(item, index) in searchFilteredItems()" :key="index">
                                    <button
                                            type="button"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                            wire:click="applyReplace({{ $selectedDetailId }}, item.id)">
                                        <span class="text-start" x-text="item.name"></span>
                                        <span class="ms-3">✔️</span>
                                    </button>
                                </template>
                            </div>
                        @elseif($replaceStatus === true && !count($replaceItems))
                            <div class="alert alert-info mb-0">لیست جایگزین خالی است.</div>
                        @else
                            <div class="alert alert-secondary mb-0">برای مشاهده لیست جایگزین، یک ردیف را انتخاب کنید.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">بستن</button>
                </div>
            </div>
        </div>
    </div>
    <div wire:ignore.self class="modal fade" id="editDatesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">ویرایش تاریخ شروع و پایان</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">تاریخ شروع (شمسی)</label>
                            <div wire:ignore>
                                <input id="dietStartJ"
                                       type="text"
                                       {{--                                       value="{{verta($dietReqeust->start_date)->format('Y/m/d')}}"--}}
                                       class="form-control @error('dateForm.start_jalali') is-invalid @enderror"
                                       data-jdp data-jdp-only-date
                                       placeholder="مثلاً ۱۴۰۴/۱۰/۰۶">
                            </div>
                            @error('dateForm.start_jalali')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">تاریخ پایان (شمسی)</label>
                            <div wire:ignore>
                                <input id="dietEndJ"
                                       type="text"
                                       class="form-control @error('dateForm.end_jalali') is-invalid @enderror"
                                       data-jdp data-jdp-only-date
                                       placeholder="مثلاً ۱۴۰۴/۱۰/۲۰">
                            </div>
                            @error('dateForm.end_jalali')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">لغو</button>

                    <button type="button"
                            class="btn btn-success"
                            wire:click="saveDates"
                            wire:loading.attr="disabled"
                            wire:loading.class="btn-loading">
                        ذخیره
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/jalali/jalalidatepicker.min.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset('assets/admin/jalali/jalalidatepicker.min.js') }}"></script>
    <script src="{{ asset('assets/admin/plugins/chart/utils.js') }}"></script>
    <script>
        // Pie Chart
        var datapie = {
            labels: ['وعده های مانده', 'وعده های مصرف شده'],
            datasets: [{
                data: [{{ $totallMeals }}, {{ $donedlMeals }}],
                backgroundColor: ['#ffcf9f', '#ffb0c1', '#ffe6aa']
            }]
        };
        var optionpie = {
            maintainAspectRatio: false,
            responsive: true,
            legend: {
                display: false
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        };
        // Doughbut Chart
        var ctx6 = document.getElementById('chartPie');
        var myPieChart6 = new Chart(ctx6, {
            type: 'doughnut',
            data: datapie,
            options: optionpie
        });
        // using transparency
        var ctx3 = document.getElementById('chartBar3').getContext('2d');

    </script>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('diet-sent', ({message}) => {
                showSwalSuccess2('موفقیت', message ?? 'انجام شد');
            });

            function showSwalSuccess2(title, text) {
                swal({
                    title: title,
                    text: text,
                    type: "success",
                    showCancelButton: false,
                    showConfirmButton: true,
                    confirmButtonText: "باشه",
                    timer: 6000,
                    closeOnConfirm: true,
                });
            }

        });
    </script>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('diet-sent', ({message}) => {
                showSwalSuccess2('موفقیت', message ?? 'انجام شد');
            });

            Livewire.on('diet-replaced', ({message}) => {
                // بستن مودال
                const modalEl = document.getElementById('replaceMealModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                showSwalSuccess2('موفقیت', message ?? 'جایگزین شد');
            });

            function showSwalSuccess2(title, text) {
                swal({
                    title: title,
                    text: text,
                    type: "success",
                    showCancelButton: false,
                    showConfirmButton: true,
                    confirmButtonText: "باشه",
                    timer: 6000,
                    closeOnConfirm: true,
                });
            }
        });
    </script>
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

            const modalEl = document.getElementById('editDatesModal');

            function openModal() {
                new bootstrap.Modal(modalEl).show();
            }

            function closeModal() {
                const m = bootstrap.Modal.getInstance(modalEl);
                if (m) m.hide();
            }

            function wireSyncInputs(prefill) {
                const startEl = document.getElementById('dietStartJ');
                const endEl = document.getElementById('dietEndJ');

                // مقدار پیش‌فرض شمسی (همون تاریخ‌های فعلی رکورد)
                startEl.value = prefill?.start_jalali ?? '';
                endEl.value = prefill?.end_jalali ?? '';

                @this.
                set('dateForm.start_jalali', startEl.value);
                @this.
                set('dateForm.end_jalali', endEl.value);

                // وقتی کاربر از تقویم انتخاب کرد، value عوض میشه => ما هم به livewire ست می‌کنیم
                const syncStart = () => @this.
                set('dateForm.start_jalali', startEl.value);
                const syncEnd = () => @this.
                set('dateForm.end_jalali', endEl.value);

                startEl.addEventListener('change', syncStart);
                startEl.addEventListener('input', syncStart);

                endEl.addEventListener('change', syncEnd);
                endEl.addEventListener('input', syncEnd);
            }

            Livewire.on('open-edit-dates-modal', ({data}) => {
                openModal();
                setTimeout(() => wireSyncInputs(data), 50);
            });

            Livewire.on('close-edit-dates-modal', () => closeModal());
        });
    </script>
@endpush
