<?php

namespace Modules\Api\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Api\Trait\ApiHandlerTrait;

class LandingController extends Controller
{
    use ApiHandlerTrait;

    public function bmiResult()
    {
        return $this->ok(data: $this->getLandingData()
        );
    }

    public function register()
    {
        return [
            'title' => ' مسیر پیشرفت و رسیدن به هدفت رو از همین الان شروع کن',
            'items' => [
                [
                    'icon' => asset('assets/application/landing/register1.png'),
                    'title' => ' پرسشنامه',
                    'sub_title' => 'دریافت اطلاعات اولیه از وضعیت فعلی شما'
                ],
                [
                    'icon' => asset('assets/application/landing/register2.png'),
                    'title' => ' دریافت برنامه رژیمی تخصصی',
                    'sub_title' => 'دریافت رژیم و دسترسی به امکانات اصلی برنامه'
                ],
                [
                    'icon' => asset('assets/application/landing/register3.png'),
                    'title' => ' انجام برنامه رژیمی با کمک پشتیبان',
                    'sub_title' => 'در کنار برنامه رژیمی پشتیبانی رایگان دارید'
                ],
                [
                    'icon' => asset('assets/application/landing/register4.png'),
                    'title' => ' دستیابی به هدف و موفقیت در برنامه',
                    'sub_title' => 'با کمک پشتیبان و عمل به برنامه به هدف برسید'
                ],
            ],
            'btn_popup' => [
                'status' => false,
                'txt' => 'کیف پول شخصی شما 50 هزارتومان شارژ شد.'
            ],
            'popup' => [
                'icon' => asset('assets/application/landing/register5.png'),
                'title' => 'تبریک ویژه!',
                'description' => [
                    [
                        'type' => 'default',
                        'txt' => 'عضویت شما تکمیل شد'
                    ],
                    [
                        'type' => 'default',
                        'txt' => 'شما می‌توانید همین الآن برنامه رژیمی تخصصی خود را  دریافت کنید.',
                    ],
                    [
                        'type' => 'green',
                        'txt' =>'به یاد داشته باشید که می‌توانید در هر زمان میزان انرژی روزانه خود را در پروفایل‌تان دوباره تنظیم کنید.'
                    ],
                ]
            ]
        ];
    }

    private function getLandingData(): array
    {
        $user = auth()->user();

        $userStartWeight = app('dietService')->getCurrentWeight($user);
        $targetWeight = $user->target_weight;
        $dayNeed = round(abs($userStartWeight - $targetWeight)) * 7;
        $bmi = app('dietService')->computeCalorie($user);
        $titleLink = 'خرید برنامه';
        if ($user->activePackage()){
            $titleLink = 'دریافت رژیم';
        }
        $res = [
            'title' => 'برنامه تخصصی شما ساخته شد.',
            'order_diet' => [
                'title' => 'خرید برنامه رژیمی تخصصی',
                'button' => $titleLink,
                'description' => ' همین الان برنامه رژیمی خودتون رو با این هدیه بگیرید و بریم برسیم به وزن هدف',
            ],
            'video_box' => [
                'image' => asset('assets/application/landing/dr_halakoee.png'),
                'video' => asset('assets/application/landing/dr_halakoee.mp4'),
                'description' => 'در این ویدئو با برنامه تخصصی آشنا میشین و میتونین نحوه ارتباط با پشتیبانی و بررسی عملکرد خودتون رو ببینین.'
            ],
            'weight_box' => [
                'start_weight' => (float)$userStartWeight,
                'target_weight' => (float)$targetWeight,
                'after_weight_plan' => 'با شرایطی که داری فقط توی ' . $dayNeed . ' روز میتونی به وزن ایده آلت برسی!',
            ],
            'green_box' => [
                'title' => ' نحوه موفقیت در رسیدن به هدف',
                'items' => [
                    [
                        'icon' => asset('assets/application/landing/icon1.png'),
                        'value' => 'آنالیز هفتگی شما به سادگی بررسی می‌شود و می‌توانید پیشرفت خود را مشاهده کنید.'
                    ],
                    [
                        'icon' => asset('assets/application/landing/icon2.png'),
                        'value' => 'به‌راحتی می‌توانید منوی غذایی خود را تغییر دهید.'
                    ],
                    [
                        'icon' => asset('assets/application/landing/icon3.png'),
                        'value' => 'با استفاده از برنامه رژیمی، بدون نیاز به محدودیت‌های کالری، به هدف خود برسید.'
                    ],
                    [
                        'icon' => asset('assets/application/landing/icon4.png'),
                        'value' => 'با استفاده از آب شمار، به بدنتان به میزان کافی آب برسانید.'
                    ],
                    [
                        'icon' => asset('assets/application/landing/icon5.png'),
                        'value' => 'رژیم غذایی خود را با غذاهای مورد علاقه‌تان تنظیم کنید.'
                    ],
                ]
            ],
            'user_comments' => [
                'title' => 'نظرات مخاطبین ما که به هدف خود رسیده اند',
                'items' => [
                    [
                        'name' => 'علی کرمانی',
                        'img' => asset('assets/application/landing/user1.png'),
                        'description' => '28 ساله',
                        'star' => 5,
                        'title' => 'راحتی در پیدا کردن رژیم مناسب با دکتر هولاکو',
                        'comment' => 'من همیشه مشکل داشتم که رژیم غذایی مناسب خودم رو پیدا کنم، ولی دکتر هولاکو این کار رو خیلی راحت کرده. سوالات و پاسخ‌ها به خوبی تنظیم شده و آموزش‌های آشپزی هم باعث شده بتونم غذاهای سالم و خوشمزه درست کنم. این اپلیکیشن رو به همه توصیه می‌کنم!',
                    ],
                    [
                        'name' => 'مینا محمدی',
                        'img' => asset('assets/application/landing/user2.png'),
                        'description' => '31 ساله',
                        'star' => 5,
                        'title' => 'راحتی در پیدا کردن رژیم مناسب با دکتر هولاکو',
                        'comment' => 'من همیشه مشکل داشتم که رژیم غذایی مناسب خودم رو پیدا کنم، ولی دکتر هولاکو این کار رو خیلی راحت کرده. سوالات و پاسخ‌ها به خوبی تنظیم شده و آموزش‌های آشپزی هم باعث شده بتونم غذاهای سالم و خوشمزه درست کنم. این اپلیکیشن رو به همه توصیه می‌کنم!',
                    ]
                ]
            ],
            'before_after' => [
                'title' => 'تصاویر مخاطبین ما',
                'items' => [
                    [
                        'icon' => asset('assets/application/landing/before_after.png?ver=1'),
                        'value' => 'رسیدن به وزن ایده آل با برنامه ریزی دقیق'
                    ] ,
                    [
                        'icon' => asset('assets/application/landing/before_after1.png'),
                        'value' => 'رسیدن به وزن ایده آل با برنامه ریزی دقیق'
                    ],
                    [
                        'icon' => asset('assets/application/landing/before_after2.png'),
                        'value' => 'رسیدن به وزن ایده آل با برنامه ریزی دقیق'
                    ],
                    [
                        'icon' => asset('assets/application/landing/before_after3.png'),
                        'value' => 'رسیدن به وزن ایده آل با برنامه ریزی دقیق'
                    ]
                ]
            ]
        ];
        return $res;

    }

    private function getDetailDiet($user)
    {
        return [
            'protein' => 30,
            'carb' => 30,
            'fat' => 30,
            'fiber' => 10,
        ];
    }
}
