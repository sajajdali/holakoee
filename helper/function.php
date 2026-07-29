<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Modules\User\app\Notifications\UserMessageNotification;

function convertToLatinNumbers($input)
{
    $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabicNumbers  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $latinNumbers   = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    return str_replace(array_merge($persianNumbers, $arabicNumbers), $latinNumbers, $input);
}
function replaceParams(string $template, array $params): string
{
    return preg_replace_callback('/%param(\d+)%/', function ($matches) use ($params) {
        $index = (int)$matches[1] - 1;
        return $params[$index] ?? $matches[0];
    }, $template);
}
function nutritionRolId(): int
{
    return 3;
}

function getCategoryCalories()
{
    $arracType = array(
        ''	=>	'هیچکدام',
        '1'	=>	'میوه',
        '2'	=>	'سبزیجات',
        '3'	=>	'مواد پروتئینی',
        '4'	=>	'لبنیات',
        '5'	=>	'غلات و حبوبات',
        '6'	=>	'نوشیدنی ها',
        '7'	=>	'خشکبار',
        '8'	=>	'تنقلات و شیرینی',
        '9'	=>	'غذاها',
        '10'	=>	'دسر',
        '11'	=>	'چربی و روغن',
        '12'	=>	'ادویه جات',
        '13'	=>	'چاشنی ها و مواد متفرقه',
    );
    return $arracType;
}
function getUnitCalories(){
    return  array(
        '100gram'	=>	' 100 گرم',
        '50gram'	=>	' 50 گرم',
        '30gram'	=>	' 30 گرم',
        '1piece'	=>	'یک عدد',
        '1tablespoon'	=>	'یک قاشق غذاخوری',
        '1cup'	=>	'یک لیوان',
        '1cup_small'	=>	'یک فنجان',
        '1skewer'	=>	'یک سیخ',
        '1tsp'	=>	'یک قاشق چایخوری',
        '1seed'	=>	'1 دانه',
        '200gram'	=>	' 220 گرم',
        '200gram'	=>	' 220 گرم',
        '110gram'	=>	' 110 گرم',
        '110gram'	=>	' 110 گرم',
        '1Spatula'	=>	'1 کفگیر',
        '1bowl'	=>	'1 کاسه متوسط',
        'milliliter'	=>	' 100 میلی لیتر',
        '1matchbox'	=>	'1 قوطی کبریت',
        '1smallpiece'	=>	'1  تکه',
        '3smallpiece'	=>	'3  تکه',
        '6smallpiece'	=>	'6  تکه',
        '1cut'	=>	'1  برش',
        '1meal'	=>	'1  وعده',
        '1roll'	=>	'1  رول',
        '10gram'	=>	' 10 گرم',
        '20gram'	=>	' 20 گرم',
        '40gram'	=>	' 40 گرم',
        '150gram'	=>	' 150 گرم',
        '200gram'	=>	' 200 گرم',
        '50milliliter'	=>	' 50 میلی لیتر',
        '200milliliter'	=>	' 200 میلی لیتر',
        '1dish'	=>	'در ۱ بشقاب ',
        '1smallbowl'	=>	' ۱ کاسه کوچک ',
        'Halfumber'	=>	' نیم عدد ',
        'Onefour'	=>	' یک چهارم عدد ',
        '5gram'	=>	'۵ گرم',
        '10milliliter'	=>	' 10 میلی لیتر',
        'halfglassess'	=>	'نیم لیوان',
        'halfglass'	=>	'نیم فنجان',
        '150milliliter'	=>	'۱۵۰ میلی لیتر',
        '15gram'	=>	'۱۵ گرم',
    );
}
function makeJsonFile($dietRequest)
{
    $result = app('dietService')->makeDietJsonFile($dietRequest);
    app('dietService')->createDietJsonFile($dietRequest , $result);

    //create pdf
    $user  = $dietRequest->user;
    $dietDetail = $result ;
    $mainFile = View::make('diet::exportDiet', compact('dietDetail', 'user', 'dietRequest'))->render();
    $fileName = 'diet-' . $dietRequest->id . '.html';
    $directory = '/tmp/pdf/holako/';
    $filePath = $directory . $fileName;

    // Ensure the directory exists
    File::ensureDirectoryExists($directory, 0777, true);

    // Save the file and set permissions
    File::put($filePath, $mainFile);
//    File::chmod($filePath, 0777);
}
function sendNotification($user , $params = [] , $notification = '')
{
    if (empty($user->fcm_token)) {
        \Log::channel('fcm')->info('User has no FCM token', [
            'user_id' => $user->id,
            'email' => $user->email ?? null,
        ]);
        return;
    }
    $notification = replaceParams( $notification , $params);
    $user->notify(new UserMessageNotification(
        title: "پیام مهمی از  داریم",
        excerpt: $notification,
        message: $notification,
    ));
}

function getCurrentSeason()
{
    $month = \Carbon\Carbon::now()->format('n');

    switch ($month) {
        case 12:
        case 1:
        case 2:
            return 3;
        case 3:
        case 4:
        case 5:
            return 0;
        case 6:
        case 7:
        case 8:
            return 1;
        case 9:
        case 10:
        case 11:
            return 2;
        default:
            return 'Unknown';
    }
}
function getFileIconClass($mimeType) {
    return match(true) {
        str_contains($mimeType, 'png') => 'image',
        str_contains($mimeType, 'jpeg') => 'image',
        str_contains($mimeType, 'jpg') => 'image',
        str_contains($mimeType, 'webp') => 'image',
        str_contains($mimeType, 'heif') => 'image',
        str_contains($mimeType, 'pdf') => 'fa-file-pdf',
        str_contains($mimeType, 'word') => 'fa-file-word',
        str_contains($mimeType, 'excel') => 'fa-file-excel',
        str_contains($mimeType, 'text') => 'fa-file-alt',
        str_contains($mimeType, 'zip') => 'fa-file-archive',
        default => 'fa-file',
    };
}
if (!function_exists('formatDecimalArray')) {
    /**
     * Format all decimal values in an array to a specific precision.
     * Integer values remain unchanged.
     *
     * @param array $array The array to format.
     * @param int $precision The number of decimal places.
     * @return array The formatted array.
     */
    function formatDecimalArray(array $array, int $precision = 2): array
    {
        return array_map(function ($value) use ($precision) {
            if (is_array($value)) {
                // If the value is an array, process it recursively
                return formatDecimalArray($value, $precision);
            } elseif (is_numeric($value)) {
                // Check if the number is a decimal
                return floor($value) != $value
                    ? number_format((float)$value, $precision, '.', '') // Format decimal numbers
                    : $value; // Leave integers as they are
            }
            // Return the value as is for non-numeric values
            return $value;
        }, $array);
    }
}

function disableCache(): bool
{
    if (env('DISABLE_CACHE') && env('DISABLE_CACHE') === true){
        return true;
    }
    return false;
}
function validData($data){
    if (!isset($data)){
        return ' - ';
    }
    if ($data == 0 || $data == -1){
        return ' - ';
    }
    return $data;
}
function camelCaseToSpace($input)
{
    // Use a regular expression to insert a space before each uppercase letter
    $result = preg_replace('/(?<!^)([A-Z])/', ' $1', $input);

    // Convert the result to lowercase
    $result = strtolower($result);

    return $result;
}

function isValidJson($string)
{
    // تلاش برای تبدیل رشته به JSON
    json_decode($string);

    // بررسی خطا
    return (json_last_error() === JSON_ERROR_NONE);
}
function getPersianDate($date)
{
    return verta($date)->format("Y/m/d : h:i:s");
}
function getPersianDateSimple($date)
{
    return verta($date)->format("Y/m/d");
}
function getPersianDateFull($date)
{
    return verta($date)->format('l j F ساعت H:i:s');
}

function clcPerUnit($quantityPerUnit, $mainNutrition): int
{
    $quantityPerUnit = is_numeric($quantityPerUnit) ? (float) $quantityPerUnit : 0.0;
    $mainNutrition = is_numeric($mainNutrition) ? (float) $mainNutrition : 0.0;

    return (int) round(($mainNutrition * $quantityPerUnit) / 100);
}
