<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\VideoController;

use Inertia\Inertia;

Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->middleware('auth')->name('dashboard');



Route::middleware('guest')->group(function () {

});

Route::middleware('auth')->group(function () {
    Route::resource('channels', ChannelController::class);
    Route::resource('channels.videos', VideoController::class);
});

require __DIR__.'/auth.php';
