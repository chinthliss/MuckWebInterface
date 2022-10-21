<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\EmailController;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('login', [LoginController::class, 'showLogin'])->name('auth.login');

Route::get('verifyemail', [EmailController::class, 'showVerifyEmail'])->name('auth.email.verify');

/*
|--------------------------------------------------------------------------
| Singleplayer Content
|--------------------------------------------------------------------------
*/
Route::prefix('/singleplayer/')->group(function() {
    Route::get('', function() {
        return view('singleplayer.home');
    })->name('singleplayer.home');
});

/*
|--------------------------------------------------------------------------
| Multiplayer Content
|--------------------------------------------------------------------------
*/
Route::prefix('/multiplayer/')->group(function() {

    // ----------------------------- Stuff that doesn't require a character
    Route::group(['middleware' => ['auth']], function() {
        Route::get('', function () {
            return view('multiplayer.home');
        })->name('multiplayer.home');
    });


});
