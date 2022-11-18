<?php

use App\Http\Controllers\Auth\AccountController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
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
    Route::post('login', [LoginController::class, 'loginAccount'])->middleware('throttle:8,1');
    Route::post('createaccount', [AccountController::class, 'createAccount'])->name('auth.create');

    //Password forgot / reset
    Route::get('forgotpassword', [PasswordController::class, 'showForgottenPassword'])->name('auth.password.forgot');
    Route::post('forgotpassword', [PasswordController::class, 'requestPasswordReset'])->middleware('throttle:3,1');
    Route::get('account/passwordreset/{id}/{hash}', [PasswordController::class, 'showPasswordReset'])
        ->name('auth.password.reset')->middleware('signed', 'throttle:8,1');
    Route::post('account/passwordreset/{id}/{hash}', [PasswordController::class, 'resetPassword'])
        ->middleware('signed', 'throttle:8,1');

});

/*
|--------------------------------------------------------------------------
| Pages that require being logged in
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth']], function () {
    Route::post('logout', [LoginController::class, 'logoutAccount'])->name('auth.logout');
    Route::get('verifyemail', [EmailController::class, 'showVerifyEmail'])->name('auth.email.verify');
    Route::get('verifyemail/{id}/{hash}', [EmailController::class, 'verifyEmail'])
        ->name('auth.email.verification')->middleware('signed', 'throttle:8,1');
    Route::get('resendverifyemail', [EmailController::class, 'resendVerificationEmail'])->name('auth.email.resendVerification');
    Route::post('termsofservice', [TermsOfServiceController::class, 'acceptTermsOfService']);
    Route::get('account', [AccountController::class, 'showAccount'])->name('account');
    Route::get('changepassword', [PasswordController::class, 'showChangePassword'])->name('auth.password.change');
    Route::post('changepassword', [PasswordController::class, 'changePassword']);
    Route::get('changeemail', [EmailController::class, 'showChangeEmail'])->name('auth.email.change');
    Route::post('changeemail', [EmailController::class, 'changeEmail']);
    Route::get('account/transactions', [AccountController::class, 'showTransactions'])->name('account.transactions');
    Route::get('account/cardmanagement', [AccountController::class, 'showCardManagement'])->name('account.cardmanagement');
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
