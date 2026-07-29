<?php

namespace Modules\Api\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\AI\Services\AiMenuService;
use Modules\Api\app\Http\Requests\PopupRequest;
use Modules\Api\app\Resources\ButtonResource;
use Modules\Api\Enum\PopupEnum;
use Modules\Api\Enum\RouteEnum;
use Modules\Api\Trait\ApiDietTrait;
use Modules\Api\Trait\ApiHandlerTrait;
use Modules\Api\Transformers\Notification\NotificationResource;
use Modules\Chat\app\Models\Chat;
use Modules\Diet\Entities\BasicFood;
use Modules\Diet\Enum\DietRequestStatusEnum;
use Modules\Diet\Popup\PopupClass;
use Modules\Package\Enum\PackageTypeEnum;
use Modules\Setting\Enum\SettingKeyEnum;
use Modules\User\app\Notifications\UserMessageNotification;
use Modules\User\Entities\User;

class DashboardController extends Controller
{
    use ApiHandlerTrait,ApiDietTrait;

    public function seeder()
    {
        $modelName = 'RecipeUnit';

        // دریافت نام جدول از مدل
        $modelClass = "Modules\\Recipe\\app\\Models\\$modelName"; // نام کامل مدل به همراه namespace
        if (!class_exists($modelClass)) {
            dd("Model $modelName does not exist.");
        }

        // دریافت داده‌ها از جدول
        $data = $modelClass::all()->toArray();

        // ساخت مسیر ذخیره
        $directoryPath = storage_path("app/seeders/");
        $fileName = "{$modelName}.json";

        // اطمینان از وجود پوشه
        if (!file_exists($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        // ذخیره اطلاعات در فایل
        file_put_contents("$directoryPath/$fileName", json_encode($data, JSON_PRETTY_PRINT));

        dd("Seeder file created at $directoryPath/$fileName");
    }
    public function seederPivot()
    {
        // نام جدول واسط
        $tableName = 'basic_food_category';

        // دریافت داده‌ها از جدول به صورت مستقیم
        $data = DB::table($tableName)->get()->toArray();

        // ساخت مسیر ذخیره
        $directoryPath = storage_path("app/seeders/");
        $fileName = "{$tableName}.json";

        // اطمینان از وجود پوشه
        if (!file_exists($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        // ذخیره اطلاعات در فایل
        file_put_contents("$directoryPath/$fileName", json_encode($data, JSON_PRETTY_PRINT));

        dd("Seeder file created at $directoryPath/$fileName");
    }
    public function getInverseOptions(string $options): string
    {
        $optionsArray = json_decode($options, true);
        $fullRange = range(0, 6);
        if (empty($optionsArray)) {
            return json_encode($fullRange);
        }
        $inverseOptions = array_values(array_diff($fullRange, $optionsArray));
        return empty($inverseOptions) ? json_encode([]) : json_encode($inverseOptions);
    }

    public function testByService(AiMenuService $ai)
    {
        $foods = ['برنج سفید','قیمه سیب زمینی','ماست'];
        $foodName = 'خورشت قیمه سیب زمینی با ماست';

        $result = $ai->fetchRaw($foods, $foodName, refresh: false);
        // $result = ['menu_items'=>[], 'restrictions_about_menu'=>[], 'meals_allowed'=>[]] | null

        return response()->json([
            'ok'     => (bool) $result,
            'result' => $result,
        ]);
    }

    public function test()
    {
        $refresh = false;
        $foods = [
            'برنج سفید',
            'قیمه سیب زمینی',
            'ماست',
        ];
        $foodName = 'خورشت قیمه سیب زمینی با ماست';

        // --- 1) دریافت دیتای مرتبط از DB
        $basicFoods = BasicFood::query()
            ->where(function ($q) use ($foods) {
                foreach ($foods as $food) {
                    $q->orWhere('name', 'LIKE', "%{$food}%");
                }
            })
            ->get();

        $foodsJson  = $basicFoods->toJson(JSON_UNESCAPED_UNICODE);
        $replaceFoods = implode('-', $foods);

        $basePrompt = setting(SettingKeyEnum::AI_PROMPT);
        $prompt = str_replace(['{foods}', '{foodName}'], [$replaceFoods, $foodName], $basePrompt);

        // آخرین زمان تغییرات جدول تا اگر چیزی تغییر کرد، کش بی‌اعتبار شود
        $foodsMaxUpdatedAt = optional(BasicFood::max('updated_at'), fn($v) => Carbon::parse($v)->timestamp) ?? 0;

        // --- 2) ساخت کلید کش یکتا و پایدار
        $cacheKey = sprintf(
            'ai:menu:v1:%s:%s',
            $foodsMaxUpdatedAt,                 // باعث invalidation خودکار روی هر تغییر دیتا
            sha1($prompt.$foodsJson)           // یکتا بر اساس محتوا
        );

        // اگر خواستید کش را دستی بی‌اعتبار کنید
        if ($refresh) {
            Cache::forget($cacheKey);
        }

        // --- 3) TTL کش
        $ttl = now()->addHours(12);

        // --- 4) جلوگیری از Thundering Herd با Lock
        // توجه: نیاز به درایور کشی دارید که lock را پشتیبانی کند (Redis/Database/Memcached)
        $lockKey = 'lock:'.$cacheKey;

        return Cache::remember($cacheKey, $ttl, function () use ($lockKey, $prompt, $foodsJson) {
            $lock = Cache::lock($lockKey, 30); // 30 ثانیه برای انجام درخواست خارجی
            try {
                // اگر همزمان یکی دیگر مشغول محاسبه است، تا 10 ثانیه صبر می‌کنیم
                return $lock->block(10, function () use ($prompt, $foodsJson) {

                    // --- 5) خودِ درخواست به مدل (با retry ساده)
                    $response = retry(2, function () use ($prompt, $foodsJson) {
                        return Http::withToken(env('OPENAI_API_KEY'))
                            ->asJson()
                            ->withOptions([
                                'http_errors'     => false,
                                'connect_timeout' => 0,
                                'timeout'         => 0,
                                'read_timeout'    => 0,
                            ])
                            ->post('https://api.openai.com/v1/responses', [
                                "model" => "gpt-5-nano",
                                "input" => [
                                    [
                                        "role" => "system",
                                        "content" => "جواب خروجی برای مشاهده کاربر نیست و دقیقا طبق promp که میده جواب بده که میخوام تو کد لاراول استفاده کنم از این جواب ".$foodsJson,
                                    ],
                                    ["role" => "user", "content" => $prompt],
                                ],
                                "stream" => false,
                            ]);
                    }, 200); // فاصله بین تلاش‌ها (میلی‌ثانیه)

                    // --- 6) هندل نتیجه (موفق/ناموفق)
                    if ($response->successful()) {
                        // چیزی که نیاز دارید را برگردانید (ترجیحاً minimal)
                        // اگر خروجی خام JSON لازم است:
                        return $response->json();
                    }

                    // نکته: شکست را در کش ذخیره نکنید. Exception بدهید تا Cache::remember چیزی ذخیره نکند.
                    throw new \RuntimeException(
                        'AI response failed. Status: '.$response->status().' Body: '.$response->body()
                    );
                });
            } finally {
                optional($lock)->release();
            }
        });
    }
    public function testNotification()
    {
        $user = User::find(25);
        $user->notify(new UserMessageNotification(

            title: "تیکت شما جواب داده شد",
            excerpt: 'به تیکت ارسالی شما پاسخ دادیم',
            message: 'salam',
        ));

    }
    public function index(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        // hande date
        $date = $request->has('date') ? Carbon::parse($request->input('date')) : Carbon::now();
        $currentDay = $date->toDateString();
        $nextDay = $date->copy()->addDay()->toDateString();
        $prevDay = $date->copy()->subDay()->toDateString();

        // Calculation section
        $userBmi = app('dietService')->userBmi($user , true);
        if (($userBmi->status ?? false) !== true) {
            return $this->badRequest([
                'status' => false,
                'message' => $userBmi->message ?? 'اطلاعات لازم برای محاسبه کالری کامل نیست.',
            ]);
        }

        $userFood = app('dietService')->userFoodConsumptionPerWeek($user , $date);
        $userWater = app('dietService')->userWaterConsumptionPerDay($user, $currentDay);
        $userMeals = app('dietService')->getUserMeals($user, $userBmi->calorie, $currentDay);

        $userFoodThisDay = $userFood[$currentDay];
        $popUp = $this->popupHandle($user);

        $activePackage = $user->activePackage();
        if ($activePackage){
            $showCalories = true;
            $showOnlineChat = true;
            $startAt = verta($activePackage->start_at)->format("Y/m/d");
            $endDate = verta($activePackage->end_at)->format("Y/m/d");

        } else {
            $showCalories = false;
            $showOnlineChat = false;
            $startAt = verta()->format("Y/m/d");
        }

        $currentWeight = (float) app('dietService')->getCurrentWeight($user);
        $targetWeight = (float) $user->target_weight;
        $overWeightOrUnderWeight = abs($currentWeight - $targetWeight);
        $weightChartTitle = $currentWeight >= $targetWeight ? 'اضافه وزن' : 'کمبود وزن';
        if (!$activePackage){
            $endDate = verta()->addDays((int) ceil($overWeightOrUnderWeight))->format("Y/m/d");
        }
//        dd($userBmi);
        return $this->ok([

            'user_id' => $user->id,
            'header' => [
//                'messages' => $user->messages()->where('is_read', 0)->count(),
                'title' => 'اپلیکیشن رژیم دکتر هلاکو',
                'icon_title' => 'هدف'
            ],
//            'dates' => [
//                'title' => (Carbon::today()->toDateString() == $currentDay ? 'امروز, ' : '') .verta($date)->format('d F'),
//                'today' => Carbon::now()->toDateString(),
//                'current_day' => $currentDay,
//                'next_day' => $nextDay,
//                'prev_day' => $prevDay,
//
//            ],


            'weight_chart' => [
                'weight' => [
                    'title' => 'وزن فعلی',
                    'value' => (string) $currentWeight
                ],
                'under_or_over_weight' => [
                    'title' => $weightChartTitle,
                    'value' => (string) $overWeightOrUnderWeight,
                ],
                'start_at' => [
                    'title' => 'تاریخ شروع',
                    'value' => (string) $startAt
                ] ,
                'end_at' => [
                    'title' => 'تاریخ اتمام',
                    'value' => (string) $endDate
                ]

            ],
            'show_diet_banner' => $this->showDietBanner($user),
            'top_chart' => [
                'calorie_this_day' => [
                    'max_allowed' => $userBmi->calorie,
                    'consumed' => $userFoodThisDay['total_calories'],
                    'percent' => $this->calculatePercentage($userBmi->calorie, $userFoodThisDay['total_calories'])
                ],
                'protein' => [
                    'max_allowed' => $userBmi->unitsNeeded->protein,
                    'consumed' => $userFoodThisDay['total_protein'],
                    'percent' => $this->calculatePercentage($userBmi->unitsNeeded->protein, $userFoodThisDay['total_protein'])
                ],
                'carb' => [
                    'max_allowed' => $userBmi->unitsNeeded->carb,
                    'consumed' => $userFoodThisDay['total_carb'],
                    'percent' => $this->calculatePercentage($userBmi->unitsNeeded->carb, $userFoodThisDay['total_carb'])
                ],
//                'fiber' => [
//                    'max_allowed' => $userBmi->unitsNeeded->fiber,
//                    'consumed' => $userFoodThisDay['total_fiber'],
//                    'percent' => $this->calculatePercentage($userBmi->unitsNeeded->fiber, $userFoodThisDay['total_fiber'])
//                ],
                'fat' => [
                    'max_allowed' => $userBmi->unitsNeeded->fat,
                    'consumed' => $userFoodThisDay['total_fat'],
                    'percent' => $this->calculatePercentage($userBmi->unitsNeeded->fat, $userFoodThisDay['total_fat'])
                ]
            ],

            'meals' => $userMeals,
//            'has_active_diet' => $this->userHasActiveDiet($user),
//            'diet_order_button' => $this->dietOrderButton($user),


//            'invite_friends' => [
//                'title' => 'دعوت از دوستان',
//                'sub_title' => 'دوستانت رو دعوت کن تا برنامت رایگان بشه',
//                'button' => ButtonResource::make([
//                    'title' => 'دعوت دوستان',
//                    'subtitle' => '',
//                    'route' => RouteEnum::PROFILE,
//                ])
//            ],


            'popup' => $popUp,
            'online_support' => [
                'active' => $showOnlineChat,
                'bg_color' => '#0CDA6B',
                'title' => 'پشتیبانی اختصاصی',
                'badge' => Chat::getRoom($user->id)->new_message_by_user,
                'chat_id' => Chat::getRoom($user->id)->id
            ],

            'food_calorie_bank' => $showCalories ? [
                'title' => 'بانک کالری غذایی',
                'sub_title' => 'مرجع جامع کالری موادغذایی ',
                'button' => ButtonResource::make([
                    'title' => 'مشاهده',
                    'subtitle' => 'مشاهده',
                    'route' =>  RouteEnum::FOOD_CALORIE_BANK,
                    'type' => 1 ,

                ]),
            ] : null

        ]);
    }

    private function getPosts()
    {
        // Cache key based on today's date
        $cacheKey = 'daily_blog_posts_' . now()->toDateString();

        // Check if cache exists, if not, fetch from API and cache it
        return Cache::remember($cacheKey, now()->addDay(), function () {
            // Make an API request to the WordPress site
            $response = Http::get('https://drholakoo.ir/wp-json/wp/v2/posts', [
                'per_page' => 5, // Limit the number of posts to 5
            ]);

            // If the request fails, return an error structure
            if ($response->failed()) {
                return [
                    'title' => 'Error',
                    'message' => 'خطا در دریافت اطلاعات از وردپرس',
                    'posts' => [],
                ];
            }

            // Process the data and format it
            $posts = collect($response->json())->map(function ($post) {
                return [
                    'title' => $post['title']['rendered'] ?? 'No title',
                    'link' => $post['link'] ?? '#', // WordPress post URL
                    'image' => null, // Placeholder for an image, if needed
                ];
            });

            // Final data structure for caching and returning
            return [
                'title' => 'بیشتر بدانید',
                'description' => 'در این بخش می‌توانید اطلاعات مهم مرتبط با رژیم غذایی و برنامه غذایی تون رو ببینید',
                'posts' => $posts,

            ];
        });
    }
//    private function getPosts()
//    {
//        $response = Http::get('https://drholakoo.com/wp-json/wp/v2/posts', [
//            'per_page' => 5,
//        ]);
//
//        if ($response->failed()) {
//            return response()->json([
//                'message' => 'خطا در دریافت اطلاعات از وردپرس',
//            ], 500);
//        }
//
//        $posts = collect($response->json())->map(function ($post) {
//            return [
//                'title' => $post['title']['rendered'] ?? 'بدون عنوان',
//                'link' => $post['link'] ?? '#', // لینک اصلی پست وردپرس
//                'image' => null,
//            ];
//        });
//
//        return [
//                'title' => 'بیشتر بدانید',
//                'description' => 'در این بخش می‌توانید اطلاعات مهم مرتبط با رژیم غذایی و برنامه غذایی تون رو ببینید',
//                'posts' => $posts,
//
//        ];
//    }

    private function popupHandle(User $user): ?array
    {
        $popupUser = $user->popup;
        if (!$popupUser){
            return null;
        }

        if ($user->popup == PopupEnum::SHOW_INFORMATION->value){
            return PopupClass::showInformation();
        }
        elseif ($user->popup == PopupEnum::FREE_ACCOUNT->value){
            return PopupClass::freeAccount();
        }
        elseif ($user->popup == PopupEnum::REJECTED_DIET->value){
            return PopupClass::rejectedPopup();
        }
        elseif ($user->popup == PopupEnum::STARTING_THE_DIET_TOMORROW->value){
            return PopupClass::starttingDeitTomorrow();
        }
        return null;


    }

    public function popup(PopupRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $field = $request->input('field');
        $value = $request->input('value');

        return PopupClass::popupStore($user, $field, $value);

    }
    public function showDietBanner($user)
    {
        // temp
        //TODO must be delete
//        return [
//            'chips' => '9 روز',
//            'button' => ButtonResource::make([
//                'title' => 'مشاهده برنامه رژیم من',
//                'subtitle' => 'مشاهده غذاهای پیشنهادی شما',
//                'route' =>  RouteEnum::ORDER_PACKAGE
//                'route' =>  RouteEnum::SHOW_DIET->parameter(2)
//            ]),
//        ];
        $userActiveDiet = $this->userHasActiveDiet($user);

        if ($userActiveDiet){
            return [
                'bg_color' =>
                    '#084976',

                'button' => ButtonResource::make([
                    'title' => 'مشاهده برنامه رژیم من',
                    'subtitle' => 'مشاهده غذاهای پیشنهادی شما',
                    'route' =>  RouteEnum::SINGLE_DIET,
                    'type' => 1 ,

                ]),

            ];
        }
        elseif ($user->pendingDiet()){
            if ($user->previousActiveDiet()){
                return [
                    'bg_color' =>  '#084976',
                    'button' => ButtonResource::make([
                        'title' => 'رژیم شما در حال تجویز است',
                        'subtitle' => 'لطفا تا تجویز رژیم منتظر بمانید تا رژیم جدید تجویز شود. تا زمان تجویز رژیم جدید میتوانید از رژیم قبلی خود استفاده کنید. برای مشاهده رژیم قبلی به قسمت رژیم مراجعه کنید',
                        'route' =>  RouteEnum::SINGLE_DIET,
                        'type' => 2,
                    ]),
                ];
            }
            return [
                'bg_color' =>  '#084976',
                'button' => ButtonResource::make([
                    'title' => 'رژیم شما در حال تجویز است',
                    'subtitle' => 'لطفا تا تجویز رژیم منتظر بمانید . پس از تجویز از طریق پیامک به شما اطلاع میدهیم',
                    'route' => RouteEnum::DASHBOARD,
                    'type' => 2,
                ]),
            ];
        }
        elseif ($user->activePackage()){
            return [
                'bg_color' =>  '#084976',
                'button' => ButtonResource::make([
                    'title' => 'دریافت رژیم',
                    'subtitle' => 'جهت دریافت برنامه تغذیه کلیک کنید',
                    'route' => RouteEnum::NEW_DIET,
                    'type' => 2,
                ]),
            ];
        } else {
            return [
                'bg_color' => '#084976',
                'button' => ButtonResource::make([
                    'title' => 'دریافت رژیم اختصاصی',
                    'subtitle' => 'رژیم مناسب شرایط خودتان را بگیرید!!',
                    'route' => RouteEnum::ORDER_PACKAGE,
                    'type' => 2,
                ]),
            ];
        }
    }

    public function userHasActiveDiet($user)
    {
        return app('dietService')->userActiveDiet($user);
    }

    private function dietOrderButton($user): ?array
    {
        if ($this->userHasActiveDiet($user)) {
            return null;
        }
        return [
            'title' => 'رژیم تخصصیت رو دریافت کن!',
            'button' => ButtonResource::make([
                'title' => 'خرید رژیم',
                'subtitle' => null,
                'route' =>  null
            ]),
            'descriptions' => [
                'توی یک ماه 6 کیلو کم کن',
                'با این رژیم ماهیچه‌هاتو از دست نمیدی',
                'پشتیبانی 24 ساعته داری',
                'و کلی ویژگی‌های دیگر'
            ]
        ];
    }

    private function checklist($user)
    {
        return [
            'title' => 'چک لیست کارای روزانه‌ت',
            'step' => '2-4',
            'percent' => 50,
            'items' => [

                [
                    'button' => ButtonResource::make([
                        'title' =>'🧑🏻' . ' اطلاعات شخصیت را وارد کن.',
                        'subtitle' => null,
                        'route' =>  null
                    ]),
                    'status' => true
                ],
                [
                    'button' => ButtonResource::make([
                        'title' =>'🍞' . ' آخرین غذایی که خوردی رو ثبت کن.',
                        'subtitle' => null,
                        'route' =>  null
                    ]),
                    'status' => false

                ],
                [
                    'button' => ButtonResource::make([
                        'title' =>'🍇' .  ' غذاهای امروزت رو کامل وارد کن.',
                        'subtitle' => null,
                        'route' =>  null
                    ]),
                    'status' => true
                ],
                [
                    'button' => ButtonResource::make([
                        'title' =>'🌟' . ' پروفایلت رو تکمیل کن.',
                        'subtitle' => null,
                        'route' =>  null
                    ]),
                    'status' => true
                ]
            ]
        ];
    }

    public function caculateColories($userActiveDiet): array
    {
        $colories = [
            'previous_day_calories' => 0,
            'total_Today_colories' => 0,
            'total_Today_counsumed_colories' => 0,
            'Used_carb' => 0,
            'Used_carb_persentage' => 0,
            'Used_fat' => 0,
            'Used_fat_persentage' => 0,
            'Used_protein' => 0,
            'Used_protein_persentage' => 0,
        ];

        if (!empty($userActiveDiet)) {
            $colories['previous_day_calories'] = ($userActiveDiet->dietRequestDetails()->whereDate('date_of_day',
                Carbon::now()->subDay())->sum('calories'));
            $colories['total_Today_colories'] = ($userActiveDiet->dietRequestDetails()->whereDate('date_of_day',
                Carbon::now())->sum('calories'));
            $colories['total_Today_counsumed_colories'] = ($userActiveDiet->dietRequestDetails()->where('is_done',
                1)->whereDate('date_of_day', Carbon::now())->sum('calories'));
            $colories['Used_fat'] = $userActiveDiet->dietRequestDetails()->where('is_done',
                1)->whereDate('date_of_day', Carbon::now())->sum('fat') ?? 0;
            $totalFat = $userActiveDiet->dietRequestDetails()->whereDate('date_of_day', Carbon::now())->sum('fat');
            if ($totalFat > 0) {
                $colories['Used_fat_persentage'] = floor(($colories['Used_fat'] / $totalFat) * 100);
            }
            $colories['Used_protein'] = $userActiveDiet->dietRequestDetails()->where('is_done',
                1)->whereDate('date_of_day', Carbon::now())->sum('protein') ?? 0;
            $totalpro = $userActiveDiet->dietRequestDetails()->whereDate('date_of_day', Carbon::now())->sum('protein');
            if ($totalpro > 0) {
                $colories['Used_protein_persentage'] = floor(($colories['Used_protein'] / $totalpro) * 100);
            }
            $colories['Used_carb'] = $userActiveDiet->dietRequestDetails()->where('is_done',
                1)->whereDate('date_of_day', Carbon::now())->sum('carb') ?? 0;
            $totalCarb = $userActiveDiet->dietRequestDetails()->whereDate('date_of_day', Carbon::now())->sum('carb');
            if ($totalCarb > 0) {
                $colories['Used_carb_persentage'] = floor(($colories['Used_carb'] / $totalCarb) * 100);
            }
        }

        return $colories;
    }

    public function weightChart()
    {
        /** @var User $user */
        $user = auth()->user();
        $userActiveDiet = $user->dietRequests()
            ->whereNotNull('diet_plan_id')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get();


        $chart_detail = [];
        if (!empty($userActiveDiet)) {
            $i = 0;
            foreach ($userActiveDiet as $userDiets) {
                $chart_detail[$i]['value'] = (int)$userDiets->user_information['WEIGHT'];
                $chart_detail[$i]['label'] = verta($userDiets->start_date)->format('d Y %B ');
                $i++;
                $chart_detail[$i]['value'] = (int)$userDiets->user_information['WEIGHT'] - 10;
                $chart_detail[$i]['label'] = verta($userDiets->start_date)->addDays(3)->format('d Y %B ');
                $i++;
                $chart_detail[$i]['value'] = (int)$userDiets->user_information['WEIGHT'] - 20;
                $chart_detail[$i]['label'] = verta($userDiets->start_date)->addDays(5)->format('d Y %B ');
            }
        }

        $start_at = '';
        $end_at = '';
        if ($userActiveDiet->last() && $userActiveDiet->last()->start_date) {
            $start_at = verta($userActiveDiet->last()->start_date)->format('Y/m/d');
            $end_at = verta($userActiveDiet->last()->end_date)->format('Y/m/d');
        }
        return $this->ok(
            [
                'status' => true,
                'title' => 'وضعیت شما',
                'sub_title' => 'چقد تا هدف مونده',
                'chart_data' => $chart_detail,
                'have_diet' => count($chart_detail) > 0,
                'target_weight' => (int)$user->target_weight,
                'start_weight' => (int)$userActiveDiet->last()?->user_information['WEIGHT'] ?? $user->weight,
                'end_date' => $start_at,
                'start_date' => $end_at,
                'description' => (string)setting(\Modules\Setting\Enum\SettingKeyEnum::WEIGHT_CHART_DESCRIPTION_APP),
            ]
        );
    }

    private function activeNextSession(User $user): bool
    {
        $activePackage = $user->activePackage();
        if ($activePackage) {
            if ($activePackage->package->type == PackageTypeEnum::DIET) {

                return $user->dietRequests()->where('status', DietRequestStatusEnum::ACTIVE)->where('active',
                    1)->whereNotNull('start_date')->whereDate('end_date', '<', Carbon::now())->exists();
            }
            if ($activePackage->package->type == PackageTypeEnum::DIET_AND_EXERCISE) {
                $diet = $user->dietRequests()->where('status', DietRequestStatusEnum::ACTIVE)->where('active',
                    1)->whereNotNull('start_date')->whereDate('end_date', '<', Carbon::now()) ?? false;
                $exercise = $user->exercisePlanRequests()->whereDate('end_at', '<=', Carbon::now())->where('status',
                    ExercisePlanRequestEnum::COMPLETED);
                if ($diet || $exercise) {
                    return true;
                }
                return false;
            }
            if ($activePackage->package->type == PackageTypeEnum::EXERCISE) {
                return $user->exercisePlanRequests()->whereDate('end_at', '<=', Carbon::now())->where('status',
                    ExercisePlanRequestEnum::COMPLETED)->exists();
            }
        }
        return false;
    }

    private function getGreetingBasedOnTime(): string
    {
        // Get the current time using Carbon
        $currentTime = Carbon::now();

        // Get the hour from the current time
        $currentHour = $currentTime->hour;

        // Determine whether it is morning, noon, afternoon, or night
        if ($currentHour >= 5 && $currentHour < 12) {
            // Morning (5:00 AM to 11:59 AM)
            return "صبح بخیر";
        } elseif ($currentHour >= 12 && $currentHour < 17) {
            // Noon (12:00 PM to 4:59 PM)
            return "ظهر بخیر";
        } elseif ($currentHour >= 17 && $currentHour < 20) {
            // Afternoon (5:00 PM to 7:59 PM)
            return "بعد از ظهر بخیر";
        } else {
            // Night (8:00 PM to 4:59 AM)
            return "شب بخیر";
        }
    }
}
