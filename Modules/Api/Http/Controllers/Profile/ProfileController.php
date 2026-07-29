<?php

namespace Modules\Api\Http\Controllers\Profile;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Api\app\Http\Requests\Api\Requests\Profile\DiseasesUserRequest;
use Modules\Api\app\Http\Requests\Api\Requests\Profile\UpdateUserDietInformationRequest;
use Modules\Api\app\Resources\ButtonResource;
use Modules\Api\Enum\RouteEnum;
use Modules\Api\Http\Requests\DietRequest;
use Modules\Api\Trait\ApiHandlerTrait;
use Modules\Api\Transformers\DiseaseResource;
use Modules\Api\Transformers\FaqResource;
use Modules\Core\Entities\Disease;
use Modules\Core\Entities\Faq;
use Modules\Diet\Entities\DietRequest as DietRequestModel;
use Modules\Diet\Enum\DietRequestStatusEnum;
use Modules\Diet\Service\DietService;
use Modules\Setting\Enum\SettingKeyEnum;
use Modules\User\Entities\User;
use Modules\User\Enum\UserMetaEnum;
use ReflectionClass;
use Verta;

class ProfileController extends Controller
{
    use ApiHandlerTrait;

    const ACTION_TALL = 'tall';
    const ACTION_WEIGHT = 'weight';
    const ACTION_BIRTHDAY = 'birthday';
    const ACTION_FIRST_NAME = 'first_name';
    const ACTION_LAST_NAME = 'last_name';
    const ACTION_CHECKOUT = 'checkout';

    public static function getDefinedConstants(): array
    {
        $reflectionClass = new ReflectionClass(self::class);
        return $reflectionClass->getConstants();
    }

    public function addWeight(Request $request)
    {
        // Validation for the weight input
        $request->validate([
            'weight' => ['required', 'numeric', 'min:30', 'max:300'],
        ], [
            'weight.required' => 'وارد کردن وزن الزامی است.',
            'weight.numeric' => 'وزن باید به صورت عدد وارد شود.',
            'weight.min' => 'وزن نمی‌تواند کمتر از ۳۰ کیلوگرم باشد.',
            'weight.max' => 'وزن نمی‌تواند بیشتر از ۳۰۰ کیلوگرم باشد.',
        ]);

        $user = auth()->user();
        $user->target_weight = request()->input('weight');

        return $this->ok([
            'status' => true,
            'message' => 'وزن با موفقیت ثبت شد'
        ]);
    }

    public function targetPage()
    {
        $user = auth()->user();
        $targetDiet = $this->getTargetDiet($user);

        if (!$targetDiet) {
            if ($user->pendingDiet()) {
                $banner = [
                    'button' => ButtonResource::make([
                        'title' => 'شما یک رژیم در حال تجویز دارید',
                        'subtitle' => 'بازگشت به پروفایل',
                        'route' => RouteEnum::DASHBOARD,
                        'type' => 2
                    ]),
                    'message' => 'پس از تجویز رژیم از طریق پیامک به شما اطلاع می دهیمser '
                ];
            } else {
                $banner = [
                    'button' => ButtonResource::make([
                        'title' => 'دریافت رژیم اختصاصی',
                        'subtitle' => 'رژیم مناسب شرایط خودتان را بگیرید!!',
                        'route' => RouteEnum::ORDER_PACKAGE,
                        'type' => 2
                    ]),
                    'message' => 'شما هنوز رژیم دریافت نکرده اید . '
                ];
            }

            $target = null;
        } else {
            $banner = null;
            $target = $this->getTargetPage($user, $targetDiet);
        }
        return $this->ok([
            'banner' => $banner,
            'target' => $target
        ]);

    }

    private function getTargetDiet(User $user): ?DietRequestModel
    {
        return $user->activeDiet()
            ?? $user->dietRequests()
                ->where('status', DietRequestStatusEnum::END)
                ->latest()
                ->first();
    }

    private function calculatePercentage($total, $part)
    {
        if ($total == 0) {
            return 0; // جلوگیری از تقسیم بر صفر
        }
        return ($part / $total) * 100;
    }

    private function getTargetDate(User $user, $activeDiet, ?float $currentWeight = null, ?float $targetWeight = null)
    {
        $currentWeight ??= $this->normalizeWeight($activeDiet
            ? ($activeDiet->user_information['WEIGHT'] ?? $user->weight)
            : $user->weight);
        $targetWeight ??= $this->normalizeWeight($user->target_weight);

        $diffWeight = abs($currentWeight - $targetWeight);
        $diffDays = (int)ceil(($diffWeight / $this->getWeeklyWeightChange($user)) * 7);

        return Carbon::parse($activeDiet->start_date)->addDays($diffDays);
    }

    public function getTargetWeightAfter(User $user, Carbon|string $date): ?string
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $user->metas()
            ->where('meta_key', UserMetaEnum::TARGET_WEIGHT)
            ->where('created_at', '>=', $date)
            ->latest('created_at')
            ->first()?->meta_value;
    }

    private function getTargetPage(User $user, DietRequestModel $activeDiet)
    {
        $startDate = Carbon::parse($activeDiet->start_date);
        $diffInDays = $startDate->diffInDays(Carbon::now());

        $total = Carbon::parse($activeDiet->end_date)->diffInDays($activeDiet->start_date) ?? 0;
//        $currentDay = Carbon::parse($activeDiet->end_date)->isFuture() ? $diffInDays + 1  : 1;
        $currentDay = Carbon::parse($activeDiet->start_date)->isToday() ? 1 : $diffInDays + 1;

        $userCurrentWeight = $this->normalizeWeight(app('dietService')->getCurrentWeight($user));
        $userTargetWeight = $this->normalizeWeight($activeDiet->user_information['TARGET_WEIGHT'] ?? null);
        $userTargetWeightAfterGetDiet = $this->getTargetWeightAfter($user, $activeDiet->start_date);
        if (isset($userTargetWeightAfterGetDiet)) {
            $userTargetWeight = $this->normalizeWeight($userTargetWeightAfterGetDiet);
        }


        return [
            'id' => $user->id,
            'diet_id' => $activeDiet->id,
            'plane_name' => $activeDiet?->dietPlan->name ?? '-',
            'top_statistics' => [
                'chart' => [
                    'total_day' => Carbon::parse($activeDiet->end_date)->diffInDays($activeDiet->start_date) ?? null,
                    'current_day' => $currentDay,
                    'percent' => round($this->calculatePercentage($total, $currentDay))
                ],
                'tile' => [
//                    'end_at' => getPersianDateSimple($activeDiet->end_date),
                    'end_at' => getPersianDateSimple($this->getTargetDate($user, $activeDiet, $userCurrentWeight, $userTargetWeight)),
                    'start_at' => getPersianDateSimple($activeDiet->start_at),
                    'calorie' => $activeDiet->calories,

                    'target_weight' => (string)$userTargetWeight,
                    'current_weight' => (string)$userCurrentWeight,
                    'start_weight' => app('dietService')->getStartWeight($user) ?? 0,
                ]
            ],
            'add_weight_button' => RouteEnum::ADD_WEIGHT,
            'calculate_target_plan' =>  $this->calculateWeightLossPlan($userCurrentWeight, $userTargetWeight, $this->getWeeklyWeightChange($user), $startDate, '۵۰۰ گرم در ماه'),
//                $this->calculateWeightLossPlan($userCurrentWeight, $userTargetWeight, 0.5, $startDate, '۵۰۰ گرم'),
//                $this->calculateWeightLossPlan($userCurrentWeight, $userTargetWeight, 1, $startDate, '۱ کیلو', true),
//                $this->calculateWeightLossPlan($userCurrentWeight, $userTargetWeight, 1.5, $startDate, '۱.۵ کیلو'),
        ];

    }

    private function normalizeWeight($weight): float
    {
        return is_numeric($weight) ? (float)$weight : 0;
    }

    function generateWeeklyCalorieSummary(array $result, Carbon $startDate, Carbon $currentDate): array
    {
        $weeklySummary = []; // Initialize the array for the weekly summary.

        // Calculate the number of days since the start of the diet.
        $daysPassed = $startDate->diffInDays($currentDate);

        // Determine the current week based on the passed days (weeks start on day 1).
        $currentWeekStartDay = (int)(floor($daysPassed / 7) * 7) + 1;

        // Iterate over the 7 days of the current week.
        for ($i = 0; $i < 7; $i++) {
            $dayNumber = $currentWeekStartDay + $i; // Calculate the day number in the result array.

            if (!isset($result[$dayNumber])) {
                // Skip if the day is out of range.
                continue;
            }

            $dayDate = $startDate->copy()->addDays($dayNumber - 1); // Calculate the actual date for the day.

            $status = $dayDate->lt($currentDate) ? true : false; // Determine if the day is past or upcoming.

            // Calculate the total calories for the day by summing up 'final_calories' in meals.
            $dailyCalories = array_reduce($result[$dayNumber], function ($sum, $meal) {
                return $sum + ($meal['final_calories'] ?? 0); // Add the calories if available.
            }, 0);

            $vDayDate = Verta::instance($dayDate);

            $weeklySummary[] = [
                'day_number' => $dayNumber, // Day number in the diet.
                'day_name' => $vDayDate->formatWord('l'), // Day name (e.g., Monday).
                'date' => getPersianDateSimple($dayDate->toDateString()), // Date in 'Y-m-d' format.
                'status' => $status, // 'past' if day is in the past, otherwise 'upcoming'.
                'total_calories' => $dailyCalories, // Total calories for the day.
            ];
        }

        return $this->updateFirstStatus($weeklySummary);
    }

    function updateFirstStatus(array $days): array
    {
        if (collect($days)->every(fn($day) => $day['status'] === false)) {
            $days[0]['status'] = true;
        }
        return $days;
    }


    public function calculateWeightLossPlan(float $currentWeight, float $targetWeight, float $weeklyWeightLoss, $startDate, $title = '-', $default = false): array
    {
        // Validate weekly weight loss input
        if ($weeklyWeightLoss <= 0) {
            return [
                'status' => 'error',
                'message' => 'Weekly weight loss must be greater than 0.',
                'total_days' => null,
                'milestones' => [],
            ];
        }

        // Calculate the total weight to lose
        $totalWeightToLose = $currentWeight - $targetWeight;

        if ($totalWeightToLose <= 0) {
            // User has already reached or is below the target weight
            return [
                'total_days' => 0,

                'milestones' => [
                    ['days' => 0, 'weight' => $currentWeight], // Starting point
                ],
            ];
        }

        // Initialize milestones with the starting weight
        $milestones = [
            [
                'target' => 1,
                'is_done' => true,
                'title' => 'وزن شروع',
                'date' => getPersianDateSimple($startDate->format('Y-m-d')),
                'weight' => $currentWeight
            ], // Starting point
        ];

        // Calculate milestones (25%, 50%, 75%, 100%) with corresponding weights
        for ($i = 1; $i <= 4; $i++) {
            $progress = $i / 4; // Progress fraction (25%, 50%, 75%, 100%)
            $weightAtMilestone = $i === 4
                ? $targetWeight
                : round($currentWeight - ($totalWeightToLose * $progress), 1); // Weight at the milestone
            $daysToMilestone = (int) ceil((($currentWeight - $weightAtMilestone) / $weeklyWeightLoss) * 7);

            $milestones[] = [
                'target' => $i + 1,
                'is_done' => $currentWeight < $weightAtMilestone,
                'title' => 'هدف ' . ' ' . app(DietService::class)->numberToText($i),
                'date' => getPersianDateSimple($startDate->copy()->addDays($daysToMilestone)->format('Y-m-d')),
                'weight' => $weightAtMilestone,
            ];
        }

        // Return the results
        return $milestones // Array of milestone days and weights

            ;
    }

    private function getWeeklyWeightChange(User $user): float
    {
        return match ((int)$user->weightChangePerWeek) {
            1 => 1,
            2 => 1.5,
            default => 0.5,
        };
    }

    public function faq()
    {
        $faq = FaqResource::collection(Faq::active()->orderBy('id')->get());
        return $this->ok([
            'faq' => $faq,
            'button_icon' => [
                'title' => 'پشتیبانی',
                'popup' => [
                    'title' => setting(SettingKeyEnum::PROFILE_FAQ_TITLE),
                    'phone' => setting(SettingKeyEnum::PROFILE_FAQ_PHONE),
                    'email' => setting(SettingKeyEnum::PROFILE_FAQ_EMAIL),
                    'button' => ButtonResource::make(
                        [
                            'title' => 'تماس با پشتیبانی',
                            'route' => 'tel:' . setting(SettingKeyEnum::PROFILE_FAQ_PHONE)
                        ]
                    )
                ]
            ]
        ]);
    }

    public function diseasesUpdate(DiseasesUserRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $user->diseases()->sync($request->input('diseases'));
        return $this->ok([
            'status' => true,
            'message' => 'اطلاعا با موفقیت ذخیره شد'
        ]);
    }

    public function diseases()
    {
        $diseases = DiseaseResource::collection(Disease::priority()->get());
        $userDiseases = auth()->user()->diseases;
        return $this->ok([
            'diseases' => $diseases,
            'user_diseases' => DiseaseResource::collection($userDiseases)
        ]);
    }


    public function userInformationUpdate(UpdateUserDietInformationRequest $request)
    {
        $user = auth()->user();
        $updates = $request->input('updates', []); // انتظار داریم یک آرایه از action/value باشه

        $messages = [];
        $status = false;

        foreach ($updates as $update) {
            $action = $update['action'] ?? null;
            $value  = $update['value'] ?? null;

            switch ($action) {
                case self::ACTION_TALL:
                    $user->tall = $value;
                    $messages[] = 'قد با موفقیت ویرایش شد';
                    $status = true;
                    break;

                case self::ACTION_WEIGHT:
                    $user->weight = $value;
                    $messages[] = 'وزن با موفقیت ویرایش شد';
                    $status = true;
                    break;

                case self::ACTION_FIRST_NAME:
                    $user->first_name = $value;
                    $messages[] = 'نام با موفقیت ویرایش شد';
                    $status = true;
                    break;

                case self::ACTION_LAST_NAME:
                    $user->last_name = $value;
                    $messages[] = 'نام خانوادگی با موفقیت ویرایش شد';
                    $status = true;
                    break;

                case self::ACTION_CHECKOUT:
                    $user->request_Payment_inviting_friends = 'ثبت درخواست تسویه پرداخت در تاریخ ' . verta()->format('Y/m/d ساعت H:i:s');
                    $messages[] = 'ثبت درخواست پرداخت در تاریخ ' . verta()->format('Y/m/d ساعت H:i:s');
                    $status = true;
                    break;

                case self::ACTION_BIRTHDAY:
                    $carbonDate = explode("/", $value);

                    $jsonData = [
                        'year'  => (int)($carbonDate[0] ?? 0),
                        'month' => (int)($carbonDate[1] ?? 0),
                        'day'   => (int)($carbonDate[2] ?? 0),
                    ];
                    $user->birthday = json_encode($jsonData);
                    $messages[] = 'تاریخ تولد با موفقیت ویرایش شد';
                    $status = true;
                    break;

                default:
                    $messages[] = 'اشتباه در ارسال پارامتر برای ' . ($action ?? 'نامشخص');
                    break;
            }
        }

        if ($status) {
            return $this->ok([
                'status' => true,
                'messages' => $messages,
            ]);
        }

        return $this->ok([
            'status' => false,
            'messages' => $messages,
        ]);
    }

    public function userInformation()
    {
        $user = auth()->user();
        $inviteFriends = $user->inviteFriends;
//        $user->request_Payment_inviting_friends = null;
        if ($user->activePackage()) {

            if ($user->activeDiet()) {
                $route = [
                    'title' => 'مشاهده رژیم',
                    'route' => RouteEnum::SHOW_DIET->parameter($user->activeDiet()->id),
                ];
            } else {
                $route = [
                    'title' => 'خرید پکیج',
                    'route' => RouteEnum::ORDER_PACKAGE,
                ];
            }
            $userPackageStatus = [
                'status' => true,
                'title' => 'وضعیت حساب شما',
                'subtitle' => 'شما بسته فعال دارید',
                'button' => ButtonResource::make([
                    $route,
                    'subtitle' => '',
                    'type' => 1,
                ])
            ];
        } else {
            $userPackageStatus = [
                'status' => false,
                'title' => 'وضعیت حساب شما',
                'subtitle' => 'شما دوره فعال ندارید',
                'button' => ButtonResource::make([
                    'title' => 'خرید دوره',
                    'subtitle' => '',
                    'route' => RouteEnum::ORDER_PACKAGE,
                    'type' => 1,

                ]),

            ];
        }
        $requestPaymentInviting = $user->request_Payment_inviting_friends;
        return $this->ok([
            'title' => 'اطلاعات کاربری',
            'user_id' => $user->getAuthIdentifier(),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'mobile' => $user->mobile ?? null,
            'email' => $user->email ?? null,
            'user_package' => $userPackageStatus,
            'user_information' => [
                'personal_info' => [
                    'title' => $user->full_name . '(ویرایش)',
                    'value' => $user->mobile ?? $user->email,
                ],
                'body_info' => [
                    'title' => 'اطلاعات بدنی شما',
                    'value' => ' ویرایش اطلاعات',
                ]
            ],

            'items' => [
                'logout' => [
                    'title' => 'خروج',
                    'route' => RouteEnum::LOGOUT,
                ],
                'invite_friends' => [
                    'title' => 'دعوت از دوستان',
                    'first_description' => 'دوستات رو دوعت کن تا علاوه بر اینکه اوناهم استفاده میکنن ، درصدشو دریافت کنی.',
                    'invite_code' => $user->invitation_code,
                    'number_invited_friends' => $inviteFriends->count() . ' نفر',
                    'benefit' => number_format($inviteFriends->sum('benefit')) . ' ریال',
                    'second_description' => 'علاوه بر این شما می‌توانید هر زمانی نسبت به دریافت موجودی کیف پول خود اقدام فرمایید. همچنین می‌توانید از این موجودی در پرداخت‌های خود استفاده کنید.',
                    'show_request_payment' => [
                        'status' => !isset($requestPaymentInviting),
                        'message' => $requestPaymentInviting
                    ],
                    'popup' =>
                        '
💰 کسب درآمد با معرفی دوستان

هر کاربر می‌تونه یک کد اختصاصی داشته باشه و با دعوت از دیگران، به راحتی درآمد کسب کنه

📌 نحوه محاسبه:
✅ ۱۵٪ به ازای هر معرفی و بیشتر

همین الان اقدام کن و این کد رو در اختیار دوستانت بذار
',

                    'request_payment' => [
                        'action' => self::ACTION_CHECKOUT,
                        'value' => 'checkout',
                        'title' => 'درخواست پرداخت',
                    ]
                ]

            ]

        ]);
    }

    public function dietInformation(): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $birthDay = json_decode($user->birth_day);
        return $this->ok([
            'title' => 'اطلاعات شخصی شما',
            'items' => [
                'tall' => [
                    'icon' => 'action',
                    'title' => 'قد',
                    'action' => self::ACTION_TALL,
                    'value' => $user->tall . ' سانتی متر',
                ],
                'first_name' => [
                    'icon' => 'action',
                    'title' => 'نام',
                    'action' => self::ACTION_FIRST_NAME,
                    'value' => $user->first_name ,
                ],
                'last_name' => [
                    'icon' => 'action',
                    'title' => 'نام خانوادگی',
                    'action' => self::ACTION_LAST_NAME,
                    'value' => $user->last_name ,
                ],
                'weight' => [
                    'icon' => 'weight',
                    'action' => 'weight',
                    'title' => 'وزن',
                    'value' => $user->weight . ' کیلوگرم',
                ],
                'activity' => [
                    'icon' => 'activity',
                    'action' => 'activity',
                    'title' => 'فعالیت',
                    'value' => UserMetaEnum::tryFrom(UserMetaEnum::ACTIVITY_PER_WEEK->value)->getOptionName($user->activityPerWeek),
                ],
                'birthday' => [
                    'icon' => 'birthday',
                    'title' => 'تاریخ تولد',
                    'action' => 'birthday',
                    'value' => $birthDay->year . '/' . $birthDay->month . '/' . $birthDay->day,
                    'birthday_array' => $birthDay
                ],
                'diseases' => [
                    'icon' => 'diseases',
                    'action' => 'diseases',
                    'title' => 'بیماری ها',
                    'value' => $user->diseases->count() . ' مورد',
                ],
            ]
        ]);
    }
}
