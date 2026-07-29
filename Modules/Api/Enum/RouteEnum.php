<?php
namespace Modules\Api\Enum;

enum RouteEnum: string
{
    case LOGOUT = '/logout';
    case DIETS = '/diets';
    case SINGLE_DIET = '/diet';
    case SHOW_DIET = '/diet/{route}';
    case INVITE_FRIENDS = '/invite';
    case FOOD_CALORIE_BANK = '/recipe';
    case REGISTER = '/statement';
    case PROFILE = '/profile';
    case ORDER_PACKAGE = '/package';
    case NEW_DIET = '/diet/request';
    case CLOSE = '/close';
    case AGREE = '/AGREE';
    case DASHBOARD = '/';
    case ADD_WEIGHT = '/add_weight';


    public function getName(): string
    {
        return match ($this) {
            self::DIETS => 'رژیم ها',
            self::SINGLE_DIET => 'مشاهده رژیم',
            self::SHOW_DIET => 'مشاهده رژیم',
            self::CLOSE => 'بستن',
            self::AGREE => 'تایید',
            self::ADD_WEIGHT => 'اضافه کردن وزن جدید',
            self::NEW_DIET => 'رژیم جدید',
        };
    }

    public function parameter(string $replacement = ''): string
    {
        return match ($this) {
            self::SHOW_DIET => str_replace('{route}', $replacement, $this->value),
            default => $this->value,
        };
    }
}
