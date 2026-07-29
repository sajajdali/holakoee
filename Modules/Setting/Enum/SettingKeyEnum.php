<?php

namespace Modules\Setting\Enum;

use App\interface\EnumHasNameInterface;
use Modules\Exercise\Entities\ExercisePlanRequest;
use Modules\Setting\Interface\SettingHasCacheInterface;
use Modules\Setting\Interface\SettingHasOptionInterface;
use Modules\Setting\Interface\SettingRenderAbleInterface;
use Modules\Setting\Interface\SettingTypeInterface;
use Modules\User\Entities\User;
use phpDocumentor\Reflection\Types\Self_;

enum SettingKeyEnum: int implements EnumHasNameInterface, SettingTypeInterface, SettingHasCacheInterface, SettingRenderAbleInterface, SettingHasOptionInterface
{
    case DEFAULT_EXERCISE_STATUS = 1;
    case SMS_API_TOKEN = 20;
    case SMS_API_LOGIN_TEMPLATE = 30;
    case SMS_API_LOGIN_TEMPLATE_WEBAPP = 32;
    case SMS_AFTER_SEND_DIET = 31;
    case SMS_AFTER_DIET_END_WEIGHT = 33;
    case SMS_AFTER_SEND_DIET_FINALITY = 34;
    case SUPPORT_USER_ROLE = 100;
    case PAYMENT_PAYSTAR_TOKEN = 150;
    case PAYMENT_PAYSTAR_SIGN = 151;
    case WEIGHT_CHART_DESCRIPTION_APP = 120;
    case PROFILE = 130;
    case PROFILE_FAQ_PHONE = 131;
    case PROFILE_FAQ_EMAIL = 132;
    case AI_PROMPT = 134;
    case AI_PROMPT_BASIC_FOOD = 135;

    case PROFILE_FAQ_TITLE = 133;
    case INVITE_FRIEND_BENEFIT = 140;
    case INSTAGRAM = 141;
    case FREE_RECHARGE = 142;
    case NOTIFICATION_AFTER_LOGIN = 143;
    case SMS_AFTER_ORDER_PACKAGE = 144;
    case SMS_AFTER_QUICK_SALE = 145;
    case SMS_AFTER_USER_PAYMENT = 146;
    case SMS_AFTER_REQUEST_DIET = 147;

    public function isSupportCache(): bool
    {
        return match ($this) {
            self::SMS_API_TOKEN => false,
            self::SUPPORT_USER_ROLE => false,
            self::SMS_API_LOGIN_TEMPLATE => false,
            self::SMS_API_LOGIN_TEMPLATE_WEBAPP => false,
            self::PAYMENT_PAYSTAR_TOKEN => false,
            self::PROFILE => false,
            self::PAYMENT_PAYSTAR_SIGN => false,
            self::WEIGHT_CHART_DESCRIPTION_APP => false,
            self::INVITE_FRIEND_BENEFIT => false,
            default => true
        };
    }

    public function getName(): string
    {
        return match ($this) {
            self::SMS_API_TOKEN => 'توکن API پیامک',
            self::DEFAULT_EXERCISE_STATUS => 'وضعیت برنامه بعد از تجویز',
            self::SUPPORT_USER_ROLE => 'گروه کاربری پشتیبانان',
            self::SMS_API_LOGIN_TEMPLATE => 'الگو پیامک ورود اندروید',
            self::SMS_API_LOGIN_TEMPLATE_WEBAPP => 'الگو پیامک ورود وب اپلیکیشن',
            self::SMS_AFTER_SEND_DIET => 'الگو پیامک پس از ارسال رژیم',
            self::SMS_AFTER_SEND_DIET_FINALITY => 'الگو پیامک پس از ارسال رژیم توسط کارشناس یا سیستم (پس از فعال شدن رژیم)',
            self::SMS_AFTER_DIET_END_WEIGHT => 'الگو پیامک پس از اتمام رژیم و ثبت وزن جدید',
            self::SMS_AFTER_ORDER_PACKAGE => 'الگو پیامک پس از خرید پکیج',
            self::SMS_AFTER_QUICK_SALE => 'الگو پیامک پس از ثبت خرید سریع',
            self::SMS_AFTER_USER_PAYMENT => 'الگو پیامک پس از ثبت خرید سریع',
            self::SMS_AFTER_REQUEST_DIET => 'الگو پیامک پس درخواست رژیم',
            self::PAYMENT_PAYSTAR_TOKEN => 'کد درگاه پرداخت پی استار',
            self::PROFILE => 'تنظیمات بخش پروفایل',
            self::PROFILE_FAQ_TITLE => ' عنوان در faq',
            self::PROFILE_FAQ_PHONE => 'شماره تماس با ما در faq',
            self::AI_PROMPT => 'mani prompt',
            self::AI_PROMPT_BASIC_FOOD => 'prompt ai basicFood',
            self::PROFILE_FAQ_EMAIL => ' ایمیل در faq',
            self::PAYMENT_PAYSTAR_SIGN => 'امضا درگاه پی استار',
            self::WEIGHT_CHART_DESCRIPTION_APP => 'متن توضیح در صفحه ی مشاهده مودار وزنی ',
            self::INVITE_FRIEND_BENEFIT => 'مبلغ مورد نظر به کاربر پس از معرفی هر یوزر',
            self::INSTAGRAM => 'آدرس پیج اینستاگرام',
            self::FREE_RECHARGE => 'شارژ رایگان (ریال)',
            self::NOTIFICATION_AFTER_LOGIN => 'پس از عضویت کاربر',
            default => ''
        };
    }

    public function render(): string
    {
        return $this->getType()->component($this->value);
    }

    public function getType(): SettingTypeEnum
    {
        return match ($this) {
            self::SMS_API_TOKEN => SettingTypeEnum::TEXT,
            self::DEFAULT_EXERCISE_STATUS => SettingTypeEnum::SELECT,
            self::SUPPORT_USER_ROLE => SettingTypeEnum::SELECT,
            self::PAYMENT_PAYSTAR_TOKEN => SettingTypeEnum::TEXT,
            self::PROFILE_FAQ_TITLE => SettingTypeEnum::TEXT,
            self::PROFILE_FAQ_PHONE => SettingTypeEnum::TEXT,
            self::AI_PROMPT => SettingTypeEnum::TEXTAREA,
            self::AI_PROMPT_BASIC_FOOD => SettingTypeEnum::TEXTAREA,
            self::PROFILE_FAQ_EMAIL => SettingTypeEnum::TEXT,
            self::PAYMENT_PAYSTAR_SIGN => SettingTypeEnum::TEXT,
            self::WEIGHT_CHART_DESCRIPTION_APP , self::NOTIFICATION_AFTER_LOGIN => SettingTypeEnum::TEXTAREA,
            self::INVITE_FRIEND_BENEFIT => SettingTypeEnum::TEXT,
            self::INSTAGRAM => SettingTypeEnum::TEXT,
            default => SettingTypeEnum::TEXT
        };
    }

    public function getDescription()
    {
        return match ($this) {
            self::SMS_AFTER_ORDER_PACKAGE ,self::SMS_AFTER_QUICK_SALE ,self::SMS_AFTER_REQUEST_DIET ,self::SMS_AFTER_DIET_END_WEIGHT , self::SMS_AFTER_SEND_DIET_FINALITY => 'پارامتر ها به ترتیب به شکل زیر باشد:
            <br />  ۱ = نام کاربر
             ',
            self::SMS_AFTER_USER_PAYMENT => 'پارامتر ها به ترتیب به شکل زیر باشد:
            <br />  ۱ = شماره کاربر
            <br />  ۲ = شماره پیگیری پرداخت
             ',
            default => ''
        };
    }
    /**
     * Radio, select and checkbox options
     */
    public function options(): array
    {
        return match ($this) {
//            self::DEFAULT_EXERCISE_STATUS => ExercisePlanRequest::getArrayForSetting(),
            self::SUPPORT_USER_ROLE => User::adminSupportRoles(),
            default => []
        };
    }
}
