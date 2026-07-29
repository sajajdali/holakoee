<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">لیست پلن های اضافه شد</h1>
        </div>
        <div class="ms-auto pageheader-btn">
            @can('create', \Modules\Diet\Entities\recipe::class)
                <a href="{{ route('admin.recipe.create') }}" class="btn btn-azure">افزودن دستور غذای جدید</a>
            @endcan
        </div>
    </div>

    @include('admin::layouts.components.alert')

    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <h3 class="card-title">همه پلن ها</h3>

                    <div class="card-options">
                        @if(isset($form['search']))
                            <button class="btn btn-secondary me-2" type="button" data-bs-toggle="collapse"
                                    wire:click='ignoresearch'
                                    data-bs-target="#advanceSearch" aria-expanded="false" aria-controls="advanceSearch">
                                نمایش همه
                            </button>
                        @endif
                        <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                                data-bs-target="#advanceSearch" aria-expanded="false" aria-controls="advanceSearch">
                            جست و جوی پیشرفته
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-5 collapse" id="advanceSearch" wire:ignore.self>
                        <div class="alert alert-info">
                            نتایج یافت شده
                            {{$recipes->total()}}
                            مورد
                        </div>
                        <form class="form-horizontal example">
                            <div class="row mb-4">
                                <label for="ID" class="col-md-2 form-label">ایدی</label>
                                <div class="col-md-10">
                                    <input class="form-control" id="id" wire:model="form.search.id"
                                           placeholder="ایدی درخواست مورد نظر"
                                           type="text">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label for="ID" class="col-md-2 form-label">نام پلن</label>
                                <div class="col-md-10">
                                    <input class="form-control" id="name" wire:model="form.search.name"
                                           placeholder="نام وعده مورد نظر"
                                           type="text">
                                </div>
                            </div>
                            <br>
                            <button class="btn btn-primary" type="button" wire:click="startSearch"
                                    wire:loading.class="bg-gray btn-loading disabled">جست و
                                جو
                            </button>
                        </form>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table text-nowrap text-md-nowrap table-bordered text-center"
                               wire:loading.class="op-0-3">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">نام دستور</th>
                                <th scope="col">دسته بندی</th>
                                <th scope="col">کالری</th>
                                <th scope="col">یشنهاد شده</th>
                                <th scope="col">فعال</th>
                                <th scope="col">عملیات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($recipes->isNotEmpty())
                                @foreach($recipes as $recipe)
                                    <tr>
                                        <td class="text-center">{{ $recipe->id }}</td>
                                        <td class="text-right">
                                            {!!  (new \Modules\Diet\Livewire\Admin\Recipe\RecipeList)->recipeImage($recipe)!!}

                                            {{ $recipe->name }}
                                        </td>
                                        <td>
                                            {{ $recipe->category->name }}
                                        </td>
                                        <td>
                                            {{ $recipe->calorie }}
                                            <br>
                                            <span class=" tag tag-light"> هر نفر : {{round($recipe->calorie / 2)}}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{$recipe->getSuggestedColor()}}">
                                                {{ $recipe->suggested == true ? 'بله' : 'خیر' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{$recipe->getBadgeColor()}}">
                                                {{ $recipe->suggested == true ? 'بله' : 'خیر' }}
                                            </span>
                                        </td>

                                        <td>
                                            @canany(['update','delete'],$recipe)
                                                <div class="btn-group mt-2 mb-2">
                                                    <button type="button" class="btn btn-primary dropdown-toggle"
                                                            data-bs-toggle="dropdown">
                                                        عملیات <span class="caret"></span>
                                                    </button>
                                                    <ul class="dropdown-menu" role="menu">
                                                        @can('update',$recipe)
                                                            <li>
                                                                <a href="{{ route('admin.recipe.edit',$recipe) }}">ویرایش
                                                                </a>
                                                            </li>
                                                        @endcan
                                                        @can('update',$recipe)
                                                            <li>
                                                                <a href="#"
                                                                   wire:confirm="آیا مطمئین هستید میخواهید یک کپی از این دستور پخت ایجاد کنید؟"
                                                                   wire:click="duplicateRecipe({{$recipe->id}})">کپی
                                                                    از
                                                                    دستور پخت
                                                                </a>
                                                            </li>
                                                        @endcan
                                                        @can('delete',$recipe)
                                                            <li><a class="delete_confirm_alert"
                                                                   data-label="حذف "
                                                                   data-id="{{ $recipe->id }}"
                                                                   href="">حذف</a>
                                                            </li>
                                                        @endcan
                                                    </ul>
                                                </div>
                                            @else
                                                <div class="btn-group mt-2 mb-2">
                                                    <button type="button" class="btn btn-default dropdown-toggle"
                                                            data-bs-toggle="dropdown">
                                                        عملیات <span class="caret"></span>
                                                    </button>
                                                </div>
                                            @endcanany
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="100%" class="text-center">
                                        <div class="alert alert-info">
                                            هیچ موردی یافت نشد
                                        </div>
                                    </td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $recipes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@push('styles')
    <style>
        .text-right {
            text-align: right !important;
        }
    </style>
@endpush
@push('scripts')
    <script src="{{admin_asset('plugins/sweet-alert/sweetalert.min.js')}}"></script>
    <script src="{{admin_asset('plugins/sweet-alert/admin.sweetalert.js')}}"></script>

    <script>
        var myCollapsible = document.getElementById('advanceSearch')
        myCollapsible.addEventListener('show.bs.collapse', function () {
        @this.set('searchPanel', 'show')
            ;
        });
        myCollapsible.addEventListener('hide.bs.collapse', function () {
        @this.set('searchPanel', '')
            ;
        })
    </script>
@endpush
