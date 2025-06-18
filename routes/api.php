<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});






use App\Http\Controllers\AppUserController;
Route::post('/account/create', [AppUserController::class, 'account_creation']);
Route::post('/washer/create', [AppUserController::class, 'washer_creation']);
Route::post('/client/create', [AppUserController::class, 'client_creation']);
Route::post('/login', [AppUserController::class, 'login']);
Route::post('/logout', [AppUserController::class, 'logout']);
Route::post('/password/reset', [AppUserController::class, 'password_reset']);
Route::post('/google/login', [AppUserController::class, 'google_login']);
//Route::post('/user/toggle-status', [AppUserController::class, 'toggleStatus']);
Route::get('/get-all-users', [AppUserController::class, 'getClients']);
Route::get('/get-all-washers', [AppUserController::class, 'getwashers']);


Route::post('/updateStatus', [AppUserController::class, 'updateStatus']);
Route::POST('/deleteUser', [AppUserController::class, 'deleteUser']);


 Route::get('/get/washers', [AppUserController::class, 'getwashers']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AppUserController::class, 'getLoggedInUser']);
    Route::post('/user/update', [AppUserController::class, 'updateLoggedInUser']);
  //  Route::get('/get/washers', [AppUserController::class, 'getwashers']);
    Route::get('/washer/balance', [AppUserController::class, 'washer_balance']);
    Route::delete('/account/delete', [AppUserController::class, 'destroy']);
    Route::post('/washer/availability', [AppUserController::class, 'toggleAvailability']);
    Route::get('/washer/availability/status', [AppUserController::class, 'Availability']);

      
});


//getClients
use App\Http\Controllers\ServiceController;
Route::post('/services/store', [ServiceController::class, 'store']);

Route::prefix('services')->group(function () {
    Route::get('/', [ServiceController::class, 'index']);
   // Route::post('/store', [ServiceController::class, 'store']);
    Route::get('/{id}', [ServiceController::class, 'show']);
    Route::PUT('/{id}', [ServiceController::class, 'update']);
    Route::delete('/{id}', [ServiceController::class, 'destroy']);


    Route::get('/prices', [ServiceController::class, 'priceFetch']);
    Route::post('/update-price', [ServiceController::class, 'priceUpdate']);


     //Route::POST('/update/price', [ServiceController::class, 'priceUpdate']);
});
Route::apiResource('services', ServiceController::class);

//////////////////////////////////////////////////////////////////////////
Route::get('/prices/fetch', [ServiceController::class, 'priceFetch']);
Route::post('/update-price', [ServiceController::class, 'priceUpdate']);
//////////////////////////////////////////////////////////////////////////



Route::middleware('auth:sanctum')->get('/get/services', [ServiceController::class, 'index']);


use App\Http\Controllers\WashingPointController;

Route::prefix('washing-point')->group(function () {
    Route::get('/', [WashingPointController::class, 'index']);
    Route::post('/add', [WashingPointController::class, 'add']);
    Route::delete('/delete/{id}', [WashingPointController::class, 'delete']);
    Route::put('/{id}', [WashingPointController::class, 'update']);
});

//Route::post('/washing-points', [WashingPointController::class, 'index']);

Route::middleware('auth:sanctum')->get('/washing-points', [WashingPointController::class, 'index']);

use App\Http\Controllers\BookingController;

 Route::get('/', [BookingController::class, 'create']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bookings', [BookingController::class, 'create']);
    Route::post('/mark/complete', [BookingController::class, 'markComplete']);
    //Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancelBooking']);
    Route::get('/bookings/active', [BookingController::class, 'activeUserBookings']);
    Route::get('/bookings/past', [BookingController::class, 'pastUserBookings']);

    Route::get('/bookings/cron/auto-complete', [BookingController::class, 'autoCompleteStaleBookings']);


    Route::get('/bookings/today', [BookingController::class, 'todayBookings']);
    Route::get('/bookings/tomorrow', [BookingController::class, 'tomorrowBookings']);
    Route::get('/bookings/week', [BookingController::class, 'weekBookings']);

});


 Route::get('/b', [BookingController::class, 'activeUserBookings']);

 //Route::get('/bookings/today', [BookingController::class, 'tomorrowBookings']);

 Route::get('/bookings/get', [BookingController::class, 'allBookings']);
 Route::post('/bookings/cancel', [BookingController::class, 'cancelBooking']);
  Route::post('bookings/mark/complete', [BookingController::class, 'markComplete']);

  Route::get('/stk', [BookingController::class, 'sendStkPush']);






use App\Http\Controllers\emailcontroller;
Route::get('/email', [emailcontroller::class, 'email']);







use App\Http\Controllers\FeedbackController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/feedback/add', [FeedbackController::class, 'addFeedback']);
    Route::get('/my-feedback', [FeedbackController::class, 'myFeedback']);
    Route::delete('/feedback/{id}', [FeedbackController::class, 'deleteFeedback']);
});
Route::get('/all-feedback', [FeedbackController::class, 'allFeedback']);
Route::delete('/delete/feedback/{id}', [FeedbackController::class, 'deleteFeedback']);

 Route::get('', [FeedbackController::class, 'myFeedback']);


use App\Http\Controllers\WasherRatingController;

 
Route::middleware('auth:sanctum')->group(function () {
   Route::get('/rate/get-car-detailers-to-rate', [WasherRatingController::class, 'getAllCarDetailersWithRatings']);

    Route::post('/rate/rate', [WasherRatingController::class, 'rateCarDetailer']);
    Route::get('/rate/car-detailer/{car_detailer_id}/rating', [WasherRatingController::class, 'getCarDetailerRating']);
    Route::delete('/rate/unrate', [WasherRatingController::class, 'deleteRating']);
});

Route::get('/api', [WasherRatingController::class, 'getAllWashersWithRatings']);



use App\Http\Controllers\StatsController;
Route::get('/client-stats', [StatsController::class, 'getClientStats']);
Route::get('/washer-stats', [StatsController::class, 'washerStats']);
Route::get('/rental-stats', [StatsController::class, 'rentalStats']);
Route::middleware('auth:sanctum')->get('/user-stats', [StatsController::class, 'userStats']);
Route::middleware('auth:sanctum')->get('/washer/dashboard/stats', [StatsController::class, 'washer_Stats']);
/*


//Route::get('/', [StatsController::class, 'washer_stats']);

use App\Http\Controllers\WasherRatingController;
Route::middleware('auth:sanctum')->get('/rate/get-washer-to-rate', [WasherRatingController::class, 'WasherRatingController']);
Route::middleware('auth:sanctum')->post('/rate', [WasherRatingController::class, 'rateWasher']);
Route::middleware('auth:sanctum')->post('/rate/unrate', [WasherRatingController::class, 'deleteRating']);
Route::gett('/rate/unrate', [WasherRatingController::class, 'getWasherRating']);





Route::get('/api', [WasherRatingController::class, 'WasherRatingController']);
/*
Route::get('/client-stats', [StatsController::class, 'getClientStats']);
Route::get('/washer-stats', [StatsController::class, 'washerStats']);
Route::get('/rental-stats', [StatsController::class, 'rentalStats']);
Route::middleware('auth:sanctum')->get('/user-stats', [StatsController::class, 'userStats']);

*/