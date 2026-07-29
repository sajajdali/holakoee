<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use Modules\Api\Http\Controllers\PaymentController;
use Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController;

Route::post('complete_register', [\Modules\Api\Http\Controllers\AuthController::class , 'completeRegister']);
Route::get('landing/bmi_result', [\Modules\Api\Http\Controllers\LandingController::class, 'bmiResult'])->name('api.landing.bmi_result');
Route::get('landing/register', [\Modules\Api\Http\Controllers\LandingController::class, 'register'])->name('api.landing.register');

//Route::get('landing', '')

Route::post('logout', 'AuthController@logout');
Route::get('me', 'UserController@me');
Route::get('notification', 'UserController@notification');
Route::post('notification', 'UserController@notificationRead');
Route::post('notification/read-all', 'UserController@notificationReadAll');
Route::post('edit', 'UserController@edit');
Route::get('exercise_plan', 'ExerciseController@plan');
Route::get('exercise_plan/{exercisePlanRequest}', 'ExerciseController@planDetails');
Route::post('document', 'DocumentController@sendRequest');
Route::post('order_package', 'DocumentController@sendRequest');
Route::prefix('payment')->group(function () {
    Route::get('transaction/{transaction_id}', 'UserController@transaction');
    Route::post('discount', [PaymentController::class, 'discount']);
    Route::post('store', [PaymentController::class, 'store']);
    Route::get('packages', [PaymentController::class, 'packages']);
});
Route::prefix('user')->group(function () {
    Route::get('landing', [\Modules\Api\Http\Controllers\UserController::class, 'landing'])->name('landing');
});

Route::prefix('profile')->group(function () {
    Route::get('dashboard', [\Modules\Api\Http\Controllers\Profile\DashboardController::class, 'index'])->name('dashboard');
    Route::post('dashboard/popup', [\Modules\Api\Http\Controllers\Profile\DashboardController::class, 'popup'])->name('dashboard.popup');


    // message
    Route::get('messages' , [\Modules\Api\Http\Controllers\Api\MessageController::class, 'index'])->name('messages');
    Route::patch('message/{id}/read', [\Modules\Api\Http\Controllers\Api\MessageController::class, 'markAsRead']);

    Route::get('faq', [\Modules\Api\Http\Controllers\Profile\ProfileController::class, 'faq'])->name('faq');
    Route::get('diseases', [\Modules\Api\Http\Controllers\Profile\ProfileController::class, 'diseases'])->name('diseases');
    Route::put('diseases', [\Modules\Api\Http\Controllers\Profile\ProfileController::class, 'diseasesUpdate'])->name('diseases_update');

    Route::get('diet_information', [\Modules\Api\Http\Controllers\Profile\ProfileController::class, 'dietInformation'])->name('diet_information');
    Route::put('user_information', [\Modules\Api\Http\Controllers\Profile\ProfileController::class, 'userInformationUpdate'])->name('user_information_update');

    Route::get('user_information', [\Modules\Api\Http\Controllers\Profile\ProfileController::class, 'userInformation'])->name('user_information');

    Route::get('target_page' , [\Modules\Api\Http\Controllers\Profile\ProfileController::class, 'targetPage'])->name('target_page');
    Route::post('add_weight' , [\Modules\Api\Http\Controllers\Profile\ProfileController::class, 'addWeight'])->name('add_weight');
//    Route::get('dashboard/weight/chart', [\Modules\Api\Http\Controllers\Profile\DashboardController::class, 'weightChart'])->name('dashboard');

    // recipe
    Route::prefix('recipe')->group(function () {
        Route::get('index', [\Modules\Api\Http\Controllers\Profile\RecipeController::class, 'index'])->name('index');
        Route::get('show/{recipe}', [\Modules\Api\Http\Controllers\Profile\RecipeController::class, 'recipe'])->name('index');
        Route::post('favorite/{recipe}', [\Modules\Api\Http\Controllers\Profile\RecipeController::class, 'favorite'])->name('index');
    });


    Route::prefix('diet_request_and_order')->group(function () {
        Route::get('get_information_pages', [\Modules\Api\Http\Controllers\DietRequestAndOrderController::class , 'getInformationPages'])->name('get_information_pages');
    });



    // consumptions
    Route::prefix('consumption')->group(function () {

        //cheat meal
        Route::post('cheat_meal/{meal}' , [ConsumptionController::class , 'cheatMeal'])->name('cheat_meal');
        Route::get('meals', [\Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController::class, 'meals'])->name('meals');
        // food
        Route::prefix('food')->group(function () {

            //favorites
            Route::get('favorites', [\Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController::class, 'favorites'])->name('favorites');


            Route::get('show/{basicFood}', [\Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController::class, 'show'])->name('show');
            Route::post('create', [\Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController::class, 'createFood'])->name('create');
            Route::delete('delete/{foodConsumption}', [\Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController::class, 'deleteFood'])->name('delete');

            // food of meals
            Route::get('meal/{meal}', [\Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController::class, 'foodOfMeal'])->name('foodOfMeal');



            Route::post('favorite/{basic_food}', [\Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController::class, 'favorite'])->name('favorite');
            //category
            Route::get('category', [\Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController::class, 'category'])->name('category');
            Route::get('category/search', [\Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController::class, 'searchFoods'])->name('category_search');
            Route::get('category/{category}', [\Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController::class, 'foods'])->name('foods');

        });


        //water
        Route::prefix('water')->group(function () {
            Route::post('create', [\Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController::class, 'createWater'])->name('create');
            Route::delete('delete/{foodConsumption}', [\Modules\Api\Http\Controllers\Profile\Consumption\ConsumptionController::class, 'deleteFood'])->name('delete');
        });
    });
});

//chat routes
Route::get('chat', 'UserChatController@index');
Route::post('chat', 'UserChatController@store');
//disses routes
Route::get('user_disease', 'UserController@disease');
Route::post('user_disease', 'UserController@storeDisease');
//diet Routes
Route::prefix('diet')->group(function () {
    Route::get('request_diet_list', 'DietController@index');
    Route::get('diet_detail/{diet_request}', 'DietController@detail');
    Route::get('show_last_diet', 'DietController@showLastDiet');

    Route::post('fasting_start_at', 'DietController@fastingStartAt');
    Route::post('is_done', 'DietController@consumedMeal');
    Route::get('changeable_meal_list/{diet_request_detail}', 'DietController@changeAbleMealList');
    Route::post('change_meal', 'DietController@change_mealv2');
    Route::post('request_new_diet', [\Modules\Api\Http\Controllers\DietController::class ,'newDiet' ]);
    Route::post('set_training_day', [\Modules\Api\Http\Controllers\DietController::class ,'setTrainingDay' ]);
});
Route::get('test', function () {
    $user = auth()->user();
    $user->notify(new \Modules\User\Notifications\UserMessageNotification(
        title: "test title",
        excerpt: "test excerpt",
        message: 'test message',
    ));
});
Route::prefix('check')->group(function () {
    Route::post('store_device' , [\Modules\Api\Http\Controllers\AuthController::class , 'storeDevice'])->name('store_device');
});
