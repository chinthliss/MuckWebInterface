<?php

use App\Http\Controllers\Auth\AccountController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\EmailController;
use App\Http\Controllers\Auth\TermsOfServiceController;
use App\Http\Controllers\Auth\HomeController;

/*
|--------------------------------------------------------------------------
| Pages always available
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'showWelcome'])->name('welcome');
Route::get('termsofservice', [TermsOfServiceController::class, 'showTermsOfService'])->name('auth.terms-of-service');
Route::get('accountlocked', [HomeController::class, 'showLocked'])->name('auth.locked');
/*
|--------------------------------------------------------------------------
| Pages that are available only when NOT logged in
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['guest']], function () {
    Route::get('login', [LoginController::class, 'showLogin'])->name('auth.login');
    Route::post('login', [LoginController::class, 'loginOrCreateAccount'])->middleware('throttle:8,1');
    Route::get('forgotpassword', [LoginController::class, 'showForgottenPassword'])->name('auth.password.forgot');
});

/*
|--------------------------------------------------------------------------
| Pages that require being logged in
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth']], function () {
    Route::post('logout', [LoginController::class, 'logoutAccount'])->name('auth.logout');
    Route::get('verifyemail', [EmailController::class, 'showVerifyEmail'])->name('auth.email.verify');
    Route::get('resendverifyemail', [EmailController::class, 'resendVerificationEmail'])->name('auth.email.resendVerification');
    Route::post('termsofservice', [TermsOfServiceController::class, 'acceptTermsOfService']);
    Route::get('account', [AccountController::class, 'showAccount'])->name('account');
});

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
    Route::group(['middleware' => ['auth', 'not.locked', 'verified', 'tos.agreed']], function() {
        Route::get('', function () {
            return view('multiplayer.home');
        })->name('multiplayer.home');
    });


});
