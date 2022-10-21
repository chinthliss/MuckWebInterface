<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\EmailController;
use App\Http\Controllers\Auth\TermsOfServiceController;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('login', [LoginController::class, 'showLogin'])->name('auth.login');

Route::get('verifyemail', [EmailController::class, 'showVerifyEmail'])->name('auth.email.verify');

Route::get('forgotpassword', [LoginController::class, 'showForgottenPassword'])->name('auth.password.forgot');

Route::get('termsofservice', [TermsOfServiceController::class, 'showTermsOfService'])->name('auth.terms-of-service');

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
