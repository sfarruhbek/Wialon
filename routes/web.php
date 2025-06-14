<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/config', [MainController::class,'config'])->name('config');
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


Route::middleware('auth')->group(function () {

    Route::get('/main', [MainController::class, 'main'])->name('home');
    Route::post('/save_road', [MainController::class, 'save_road'])->name('save_road');

    Route::get('/', [MainController::class, 'index'])->name('index');
});

Route::get('/map', function () {
    return view('map');
});

Route::post('/save-route', [MainController::class, 'saveRoute']);
Route::get("/rview", [MainController::class, 'roadView'])->name('roadView');

Route::get("/busstop", [MainController::class, 'busStop'])->name('busStop');
Route::post('/save-bus-stops', [MainController::class, 'saveBusStop']);
Route::post('/update-bus-stops', [MainController::class, 'updateBusStop']);

Route::get("/roads-busstops", [MainController::class, 'rbs'])->name('rbs');
Route::post('/save-rbs', [MainController::class, 'saveRBS']);
Route::post('/update-rbs', [MainController::class, 'updateRBS']);

Route::get('/asdf', [ApiController::class, 'index']);


Route::get('api/wait', [ApiController::class, 'wait'])->name('api.wait');




Route::get('/bus-location', [ApiController::class, 'getBusLocation'])->name('busLocation');
Route::get('/all-buses', [ApiController::class, 'getAllBusesLocation'])->name('all-buses');

Route::get('/wait', function (){ return view('wait');})->name('wait');
