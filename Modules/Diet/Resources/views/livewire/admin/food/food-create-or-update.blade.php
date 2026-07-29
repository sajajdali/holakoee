<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">
                {{ $isEdited ? 'ویرایش غذا' : 'افزودن غذای جدید' }}
            </h1>
        </div>
    </div>

    @include('admin::layouts.components.alert')
    <div class="mt-3 mb-4">
        <button type="button" class="btn btn-warning float-start mb-3" wire:click="openAiModal">
            پیشنهاد هوشمند از AI
        </button>
        <div class="clearfix"></div>
    </div>
    {{--    <button type="button" class="btn btn-warning" wire:click="aiPrefill">پیشنهاد هوشمند از AI</button>--}}
    <form wire:submit.prevent>
        <div class="row row-sm">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom">
                        <h3 class="card-title">
                            {{ $isEdited ? 'ویرایش غذا' : 'افزودن غذا جدید' }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="col-xl-12 col-lg-12 col-md-6 col-sm-12 mb-3">
                                <label for="name">نام غذا</label>
                                <input type="text" class="form-control @error('form.name') is-invalid @enderror"
                                       id="name"
                                       wire:model="form.name" placeholder="نام">
                                @error('form.name')
                                <div id="validationName"
                                     class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            @unless($fetchData['parent_id'])
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 mb-3">
                                    <label for="name">گام تجویز بین غذاها </label>
                                    <select wire:model='form.interval_step'
                                            class="form-control @error('form.interval_step') is-invalid @enderror "
                                            name="interval_step" id="interval_step">
                                        <option value="">در نظر گرفته نشود...</option>
                                        @for ($i = 0; $i <= 20; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            @endunless
                        </div>
                        <div class="form-row">
                            <div class="table-responsive">
                                @if($fetchData['parent_id'])
                                    <div class="alert alert-warning">
                                        شما در حال ویرایش یکی از زیر دستورها هستید. در نظر داشته باشید در صورتی که
                                        دستور اصلی ویرایش یا حذف شود ، تمامی زیر دستور ها تغییر میکند
                                    </div>
                                @endif
                                <div class="alert alert-info">
                                    در صورتی که برای هرچ کدام از ایتم ها حداکثر مقدار تجویز را مشخص نکنید ، سیتم به صورت
                                    پیش فرض برای کمتری مقدار که
                                    در لیست موجود است ، حداکثر ۲۰ برابر را انتخاب میکند.
                                    <br>
                                    بهتر است شما حداقل برای یکی از ایتم ها حداکثر تعریف کنید
                                </div>
                                @error('form.main')
                                <div id="validationName"
                                     class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <table class="table  border text-nowrap text-md-nowrap">

                                    <tbody>
                                    @for($i = 0 ; $i < $form['countBasicFoods'] ; $i++ )
                                        <tr>
                                            <td>{{$i+1}}</td>
                                            <td>
                                                <div class="col-xl-12 col-lg-12 col-md-6 col-sm-12 mb-3">
                                                    <label for="name">نام غذا</label>

                                                    <select dir="rtl"
                                                            class="form-control select2-show-search form-select basic_foods"
                                                            data-placeholder="انتخاب کنید">
                                                        <option label="انتخاب کنید"></option>
                                                        @foreach($fetchData['BasicFoods'] as $basicFood)
                                                            <option data-number="{{$i}}" value="{{ $basicFood->id }}"
                                                                    @if(isset($form['foods'][$i]['basic_food_id']) && $basicFood->id == $form['foods'][$i]['basic_food_id'] ) SELECTED @endif>
                                                                {{ $basicFood->name }}
                                                                [{{$basicFood->food_fact['CALORIE']}} کالری]
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </td>
                                            @unless($fetchData['parent_id'])
                                                <td>
                                                    <div
                                                        class="col-xl-12 col-lg-12 col-md-6 col-sm-12 mb-3 @error('form.main') is-invalid @enderror">

                                                        <label for="name">امکان گسترش</label>
                                                        <br>
                                                        @isset($form['foods'][$i]['unit_name'])
                                                            <input type="checkbox" wire:model="form.foods.{{$i}}.extend"
                                                                   name="extend"

                                                                   value="{{$form['foods'][$i]['extend'] ?? ''}}">
                                                        @endisset
                                                    </div>
                                                </td>
                                            @endunless
                                            <td>
                                                <div class="col-xl-12 col-lg-12 col-md-6 col-sm-12 mb-3">
                                                    <label for="name">میزان</label>

                                                    @isset($form['foods'][$i]['unit_name'])
                                                        <select wire:model.live="form.foods.{{$i}}.unit" dir="rtl"
                                                                class="form-control">
                                                            <option
                                                                value="{{ 0.25}}"> {!!  0.25 !!} {!!  $form['foods'][$i]['unit_name'] !!}</option>
                                                            <option
                                                                value="{{  0.5}}"> {!!   0.5 !!} {!!  $form['foods'][$i]['unit_name'] !!}</option>
                                                            <option
                                                                value="{{  0.75}}"> {!!  0.75 !!} {!!  $form['foods'][$i]['unit_name'] !!}</option>
                                                            @for($b = 01 ; $b < 40 ; $b++ )
                                                                <option
                                                                    value="{{$b}}"> {!!  $b !!} {!!  $form['foods'][$i]['unit_name'] !!}</option>
                                                                <option
                                                                    value="{{ $b +  .25}}"> {!!  $b +  .25 !!} {!!  $form['foods'][$i]['unit_name'] !!}</option>
                                                                <option
                                                                    value="{{ $b +  .5}}"> {!!  $b +  .5 !!} {!!  $form['foods'][$i]['unit_name'] !!}</option>
                                                                <option
                                                                    value="{{ $b +  .75}}"> {!!  $b +  .75 !!} {!!  $form['foods'][$i]['unit_name'] !!}</option>
                                                            @endfor
                                                        </select>
                                                    @endisset
                                                </div>
                                            </td>
                                            <td>
                                                <div class="col-xl-12 col-lg-12 col-md-6 col-sm-12 mb-3">
                                                    <label for="name">حداکثر تجویز</label>
                                                    @isset($form['foods'][$i]['unit_name'])
                                                        <select wire:model.live="form.foods.{{$i}}.max" dir="rtl"
                                                                class="form-control">
                                                            <option value=""> سیستمی</option>
                                                            @for($b = 01 ; $b < 40 ; $b++ )
                                                                @continue($b <$form['foods'][$i]['unit']  )
                                                                <option
                                                                    value="{{$b}}">   {!!  $b !!} {!!  $form['foods'][$i]['unit_name'] !!}</option>
                                                                <option
                                                                    value="{{ $b +  .25}}"> {!!  $b +  .25 !!} {!!  $form['foods'][$i]['unit_name'] !!}</option>
                                                                <option
                                                                    value="{{ $b +  .5}}"> {!!  $b +  .5 !!} {!!  $form['foods'][$i]['unit_name'] !!}</option>
                                                                <option
                                                                    value="{{ $b +  .75}}"> {!!  $b +  .75 !!} {!!  $form['foods'][$i]['unit_name'] !!}</option>
                                                            @endfor
                                                        </select>
                                                    @endisset
                                                </div>
                                            </td>
                                            <td>
                                                @if($i == 0)
                                                    <button wire:click="addBasicFoodItem" type="button"
                                                            class="btn btn-icon  btn-success"><i
                                                            class="fe fe-plus"></i></button>
                                                @else
                                                    <button wire:click="removeBasicFoodItem({{$i}})" type="button"
                                                            class="btn btn-icon btn-danger"><i
                                                            class="fe fe-minus"></i></button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endfor

                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="form-row">
                            <h3>جزئیات :</h3>
                            <div class="table-responsive">
                                <table class="table table-bordered text-nowrap">
                                    <tr>
                                        <td>حداقل کالری تجویزی</td>
                                        <td>
                                            <h3>
                                                <span
                                                    class="badge font-weight-semibold bg-success-transparent text-danger tx-15">{{$computData['min_calorie']}}</span>
                                                @if(isset($computData['maximum_amount_of_food']))
                                                    تا
                                                    <span
                                                        class="badge font-weight-semibold bg-success-transparent text-danger tx-15">{{$computData['maximum_amount_of_food'] * $computData['min_calorie']}}</span>

                                                @endif
                                                @if($this->getSmallestBasicFoodId() !== false)
                                                    <span
                                                        class="badge  my-1  font-weight-semibold bg-danger-transparent text-danger tx-15">( سیستمی تا ۲۰ برابر)</span>
                                                @endif
                                            </h3>
                                        </td>
                                        <td>حداکثر افزایش</td>
                                        <td>
                                            <h3>
                                                <span
                                                    class="badge font-weight-semibold bg-success-transparent text-danger tx-15">{{$computData['maximum_amount_of_food'] ?? $this->fetchData['max'] . ' برابر و سیستمی'}}</span>
                                                @if(isset($computData['maximum_amount_of_food']))
                                                    برابر
                                                @endif
                                            </h3>
                                        </td>


                                    </tr>
                                    <tr>
                                        <td>پروتئین</td>
                                        <td>
                                            <h5>
                                                <span
                                                    class="badge font-weight-semibold bg-success-transparent text-danger tx-15">{{$computData['protein']}}</span>
                                                گرم
                                            </h5>
                                        </td>

                                        <td>کربوهیدرات</td>
                                        <td>
                                            <h5>
                                                <span
                                                    class="badge font-weight-semibold bg-success-transparent text-danger tx-15">{{$computData['carb']}}</span>
                                                گرم
                                            </h5>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>فیبر</td>
                                        <td>
                                            <h5>
                                                <span
                                                    class="badge font-weight-semibold bg-success-transparent text-danger tx-15">{{$computData['fiber']}}</span>
                                                گرم
                                            </h5>
                                        </td>

                                        <td>چربی</td>
                                        <td>
                                            <h5>
                                                <span
                                                    class="badge font-weight-semibold bg-success-transparent text-danger tx-15">{{$computData['fat']}}</span>
                                                گرم
                                            </h5>

                                        </td>
                                    </tr>
                                    <tr>
                                        <td>دستور پخت</td>
                                        <td colspan="3">
                                            {!! $computData['recipe'] !!}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-xl-12 col-lg-12 col-md-6 col-sm-12 mt-3">
                                <div class="col-12 ">
                                    <div class="@error('form.meals') border border-danger @enderror ps-1">
                                        <div class="form-group">
                                            <label class="form-label"> وعده هایی که این غذا میتواند در آن تجویز
                                                شود</label>
                                            <div class="selectgroup selectgroup-pills">
                                                @foreach($fetchData['mealList'] as $mealKey => $meal)
                                                    <label class="selectgroup-item">
                                                        <input type="checkbox" name="form.meals[]" value="{{$meal->id}}"
                                                               @if(in_array($meal->id , $form['meals'])) checked
                                                               @endif class="selectgroup-input"
                                                               wire:model.live="form.meals">
                                                        <span class="selectgroup-button">{{$meal->name}}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @error('form.meals')
                                    <div id="validationName"
                                         class="invalid-feedback d-block">{{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>


                        </div>


                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <h3 class="card-title">انتخاب شرایط خاص</h3>
                </div>
                <div class="card-body">
                    <div class="row">

                        @foreach($fetchData['conditions'] as $condition)

                            <div class="col-12 ">
                                <div class="">
                                    <div class="form-group">
                                        <label
                                            class="form-label"> {{$condition->key->getName()}} {{$condition->key}}</label>
                                        <div class="selectgroup selectgroup-pills">
                                            @if($condition->key->value == \Modules\Diet\Enum\ConditionKeyEnum::DISEASE->value)
                                                @foreach($fetchData['disease'] as $mealKey => $metaValue)
                                                    <label class="selectgroup-item">
                                                        <input type="checkbox" name="form.foodRestriction[]"
                                                               value="{{$mealKey}}"
                                                               class="selectgroup-input"
                                                               wire:model="form.conditions.{{$condition->id}}.{{$metaValue->id}}">
                                                        <span class="selectgroup-button">{{$metaValue->name}}</span>
                                                    </label>
                                                @endforeach
                                            @else
                                                @foreach($condition->options['items']['values'] as $optionKey => $optionValue)
                                                    <label class="selectgroup-item">
                                                        <input type="checkbox" name="form.foodRestriction[]"
                                                               value="{{$optionKey}}"
                                                               class="selectgroup-input"
                                                               wire:model="form.conditions.{{$condition->id}}.{{$optionKey}}">
                                                        <span class="selectgroup-button">{{$optionValue}}</span>
                                                    </label>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>
                            @unless($loop->last)
                                <hr>
                            @endunless
                        @endforeach
                        @error('*')
                        <div class="col-md-12 mb-3 alert alert-danger fade show" role="alert">
                            لطفا خطا موجود را برطرف کنید
                        </div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary"
                            wire:loading.class="bg-gray btn-loading disabled"
                            wire:click="updateOrCreate">
                        @if($isEdited)
                            ویرایش وعده
                        @else
                            ایجاد وعده
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="modal fade" id="aiPrefillModal" tabindex="-1" aria-labelledby="aiPrefillLabel" aria-hidden="true"
         wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                {{-- Header با اکشن در سمت چپ --}}
                <div class="modal-header d-flex align-items-center justify-content-between">
                    {{-- ستون راست: تیتر --}}
                    <h5 id="aiPrefillLabel" class="modal-title m-0">
                        پیشنهاد هوشمند از AI
                        @if($showCache)
                            <small class="text-muted">— سوابق کش‌شده</small>
                        @endif
                    </h5>

                    {{-- ستون وسط: دکمه بستن --}}
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="بستن"></button>

                    {{-- ستون چپ: دکمه مشاهده سوابق/بازگشت --}}
                    <div class="ms-auto">
                        @if(!$showCache)
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="showCacheList">
                                مشاهده سوابق <span class="badge bg-secondary">{{ $cachedTotal }}</span>
                            </button>
                        @else
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="backToForm">
                                بازگشت به فرم
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Body --}}
                <div class="modal-body">

                    {{-- تب سوابق: همیشه قابل مشاهده (حتی بدون توکن) --}}
                    @if($showCache)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">سوابق پیشنهادهای کش‌شده</h6>
                            <small class="text-muted">({{ $cachedTotal ?? 0 }} نتیجه)</small>
                        </div>

                        @if(($cachedTotal ?? 0) === 0)
                            <div class="alert alert-secondary mb-0">هنوز نتیجهٔ کش‌شده‌ای ندارید.</div>
                        @else
                            <div class="list-group mb-3">
                                @foreach(($cachedItems ?? []) as $ci)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="me-3">
                                            <div class="fw-bold">{{ $ci['title'] ?? 'بدون عنوان' }}</div>
                                            @if(!empty($ci['ts']))
                                                <small
                                                    class="text-muted">{{ \Carbon\Carbon::createFromTimestamp($ci['ts'])->diffForHumans() }}</small>
                                            @endif
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm"
                                                wire:click="useCachedAi('{{ $ci['key'] }}')">
                                            استفاده
                                        </button>
                                    </div>
                                @endforeach
                            </div>

                            @php
                                $per   = max(1, $cachedPerPage ?? 5);
                                $total = (int) ($cachedTotal ?? 0);
                                $page  = (int) ($cachedPage ?? 1);
                                $pages = (int) ceil($total / $per);
                            @endphp
                            @if($pages > 1)
                                <nav>
                                    <ul class="pagination justify-content-center mb-0">
                                        <li class="page-item {{ $page<=1 ? 'disabled':'' }}">
                                            <a class="page-link" href="#"
                                               wire:click.prevent="loadCachedIndex({{ max(1,$page-1) }})">قبلی</a>
                                        </li>
                                        @foreach(range(1,$pages) as $p)
                                            <li class="page-item {{ $p==$page ? 'active':'' }}">
                                                <a class="page-link" href="#"
                                                   wire:click.prevent="loadCachedIndex({{ $p }})">{{ $p }}</a>
                                            </li>
                                        @endforeach
                                        <li class="page-item {{ $page>=$pages ? 'disabled':'' }}">
                                            <a class="page-link" href="#"
                                               wire:click.prevent="loadCachedIndex({{ min($pages,$page+1) }})">بعدی</a>
                                        </li>
                                    </ul>
                                </nav>
                            @endif
                        @endif

                        {{-- تب فرم --}}
                    @else
                        @if(!$aiHasTokens)
                            <div class="alert alert-warning d-flex align-items-center" role="alert">
                                <i class="fe fe-alert-triangle ms-2"></i>
                                <div>
                                    شما هیچ توکنی ندارید. برای استفاده از پیشنهاد هوشمند، ابتدا باید توکن تهیه کنید.
                                    <a href="#" class="alert-link">خرید توکن</a>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-light" role="alert">
                                موجودی توکن شما: <strong>{{ number_format($aiTokenBalance) }}</strong>
                            </div>
                        @endif

                        {{-- فرم فقط وقتی نشان داده شود که توکن دارید --}}
                        @if($aiHasTokens)
                            <div class="mb-3">
                                <label class="form-label">نام غذا/دستور</label>
                                <input type="text"
                                       class="form-control @error('aiForm.food_name') is-invalid @enderror"
                                       placeholder="مثال: باقالی پلو با ماهیچه"
                                       wire:model.defer="aiForm.food_name">
                                @error('aiForm.food_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-0">
                                <label class="form-label">مواد/غذاها (با ویرگول یا هر خط جدا)</label>
                                <textarea rows="3"
                                          class="form-control @error('aiForm.foods') is-invalid @enderror"
                                          placeholder="مثال: ماهیچه، باقالی پلو"
                                          wire:model.defer="aiForm.foods"></textarea>
                                @error('aiForm.foods')
                                <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                @error('aiForm.tokens')
                                <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            {{-- فقط وقتی فرم اصلی باز است و کاربر توکن دارد --}}
                            @if($aiHasTokens && !$showCache)
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" id="forceRefreshSwitch"
                                           wire:model="aiForceRefresh">
                                    <label class="form-check-label" for="forceRefreshSwitch">
                                        ارسال بدون استفاده از کش
                                    </label>
                                    <small class="text-muted d-block mt-1">
                                        با فعال‌سازی این گزینه، حتی اگر نتیجهٔ کش‌شده وجود داشته باشد، درخواست جدید به
                                        AI ارسال می‌شود.
                                    </small>
                                </div>
                            @endif

                            {{-- شمارنده فقط هنگام ارسال --}}
                            <div class="mt-3" wire:loading wire:target="submitAiPrefill"
                                 x-data x-init="
                   (() => {
                     let n = 60;
                     const el = $refs.counter;
                     el.textContent = n;
                     const id = setInterval(() => { if(n>0){ n--; el.textContent=n; } else { clearInterval(id); } }, 1000);
                     return () => clearInterval(id);
                   })()
                 ">
                                <div class="alert alert-info d-flex align-items-center mb-0">
                                    <i class="fe fe-clock ms-2"></i>
                                    <div>
                                        این درخواست ممکن است تا <b><span x-ref="counter">60</span></b> ثانیه طول بکشد.
                                        لطفاً تا پایان پردازش از <strong>بستن</strong> یا <strong>رفرش</strong> صفحه
                                        خودداری کنید.
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Footer --}}
                <div class="modal-footer d-flex justify-content-between">
                    @if($showCache)
                        <button class="btn btn-outline-secondary" type="button" wire:click="backToForm">بازگشت به فرم
                        </button>
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">بستن</button>
                    @else
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal"
                                wire:loading.attr="disabled" wire:target="submitAiPrefill">
                            بستن
                        </button>

                        <button class="btn btn-primary ms-auto" type="button"
                                wire:click="submitAiPrefill"
                                wire:loading.attr="disabled"
                                wire:target="submitAiPrefill"
                                @if(!$aiHasTokens) disabled @endif>
                            <span wire:loading.remove wire:target="submitAiPrefill">اجرای پیشنهاد</span>
                            <span wire:loading wire:target="submitAiPrefill">
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              در حال پردازش...
            </span>
                        </button>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>


<!-- Row -->
<!-- Row Closed -->





@push('scripts')
    <!-- SELECT2 JS -->
    <script src="{{admin_asset('plugins/select2/select2.full.min.js')}}"></script>
    <script>
        $(document).ready(function () {

            Livewire.on('updateUi', () => {
                setTimeout(function () {
                    $('.basic_foods').select2({
                        dir: "rtl",
                        allowClear: false,
                        multiple: false,
                        searchInputPlaceholder: 'جستجو',
                        search: true,
                        width: '100%',
                    });
                }, 50);
            });

            $('.basic_foods').select2({
                dir: "rtl",
                allowClear: false,
                multiple: false,
                searchInputPlaceholder: 'جستجو',
                search: true,
                width: '100%',
            });

            $('body').on('change', '.basic_foods', function () {

                var selectedOption = $(this).find(':selected');
                let value = selectedOption.val();
                let number = selectedOption.data('number')
                    @this.dispatch('addBasicFood', {basicFoodId: value, foodNumber: number})
                ;

            });

        });
    </script>
    <style>
        .select2-container {
            width: 100% !important;
        }
    </style>
@endpush
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('aiPrefillModal');
            if (!modalEl) return;

            let aiModal = null;

            // وقتی Livewire درخواست نمایش داد
            window.addEventListener('show-ai-modal', () => {
                aiModal = aiModal || new bootstrap.Modal(modalEl, {backdrop: 'static'});
                aiModal.show();
            });

            // وقتی Livewire درخواست بستن داد
            window.addEventListener('hide-ai-modal', () => {
                if (aiModal) aiModal.hide();
            });
        });
    </script>
@endpush
