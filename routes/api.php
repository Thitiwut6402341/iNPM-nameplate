<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DevicesController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\QRController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\CallingApiDeviceController;
use App\Http\Controllers\SecondDisplayController;


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

Route::get('/', function () {
    return response()->json(["message" => "Welcome to iNPM API."]);
});

// Login admin
Route::prefix('iNPM')->controller(LoginController::class)->group(function () {
    Route::post('/login', 'login'); // login
});

//  Home page
Route::prefix('iNPM')->controller(HomePageController::class)->group(function() {
    Route::get('/home', 'sendInformation'); //send meeting information from api to web page
    Route::put('/receive-seat', 'receiveNameplateInfo'); // select seat for attendance
    Route::post('/get-seat-info', 'getSeatInfo'); // send seat information and book details
    Route::get('/get-employees-list', 'getEmployeeList');
    Route::get('/present-meeting', 'presentMeeting');
    Route::get('/history-meeting', 'historyMeeting');
    Route::get('/snc-employees', 'getSNCEmployees');

});

// Device mangement
Route::prefix('iNPM')->controller(DevicesController::class)->group(function () {
    Route::post('/add-device', 'addDevice'); // register new device
    Route::put('/edit-device', 'editDevice'); // edit device's name
    Route::put('/edit-status', 'editStatus'); // change device's status
    Route::get('/get-device', 'getDevice'); // get all devices name
    Route::post('/active-device', 'activeDevice'); // check signal from device and active it together with send line notificartion
    Route::post('get-free-device','getFreeDevice'); // get available devices for each period
    Route::post('get-signal-api','getSignalAPI'); // receive % battery and serial no, then record to database
});

// QR code
Route::prefix('iNPM')->controller(QRController::class)->group(function () {
    Route::post('/login-QR', 'loginQR'); // login
    Route::post('/add-qr' , 'addInfobyQR'); // add seat for nameplate by qr
    Route::get('/get-seat-qr', 'getSeatQR'); // get asigned seat by qr
});

// Calling template to device
Route::prefix('iNPM')->controller(CallingApiDeviceController::class)->group(function () {
    Route::post('/calling', 'calling'); // sending template info to each device
});

//second display
Route::prefix('iNPM')->controller(SecondDisplayController::class)->group(function () {
    // Main category
    Route::post('/add-main-cate', 'addMainCategories');
    Route::get('/get-main-cate', 'getMainCategories');
    Route::put('/edit-main-cate', 'editMainCategories');
    Route::delete('/delete-main-cate', 'deleteMainCategories');

    //Sub category
    Route::post('/add-sub-cate', 'addSubCategories');
    Route::get('/get-sub-cate', 'getSubCategories');
    Route::put('/edit-sub-cate', 'editSubCategories');

    //send notification
    Route::post('/second-dis-notify', 'secondNotification');
    Route::get('/get-notification-dis2', 'getNotification');
    Route::get('/rise-hand', 'riseHand');

    //current position
    Route::post('/current-position', 'currentPosition');

    // rise hand
    Route::put('/rise-hand', 'riseHandShow');
    Route::get('/show-each', 'showEach');
});

