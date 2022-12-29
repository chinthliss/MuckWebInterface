<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AccountController;
use App\Http\Controllers\Auth\EmailController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\TermsOfServiceController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MultiplayerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core resources that are always available
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'showWelcome'])->name('welcome');
Route::get('termsofservice', [TermsOfServiceController::class, 'showTermsOfService'])->name('auth.terms-of-service');
Route::get('accountlocked', [HomeController::class, 'showLocked'])->name('auth.locked');

/*
|--------------------------------------------------------------------------
| Core resources that are available only when NOT logged in
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
| Core resources that require being logged in
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
    Route::get('newemail', [EmailController::class, 'showNewEmail'])->name('auth.email.new');
    Route::post('newemail', [EmailController::class, 'newEmail']);
    Route::post('changeemail', [EmailController::class, 'changeEmail'])->name('auth.email.change');
    Route::get('account/transactions', [AccountController::class, 'showTransactions'])->name('account.transactions');
    Route::get('account/cardmanagement', [AccountController::class, 'showCardManagement'])->name('account.cardmanagement');

});

/*
|--------------------------------------------------------------------------
| Core resources that require some sort of administrative power
|--------------------------------------------------------------------------
*/
Route::prefix('/admin/')->group(function () {

    // ----------------------------- Staff level
    Route::group(['middleware' => ['auth', 'role:staff']], function () {
        Route::get('', [AdminController::class, 'showHome'])->name('admin.home');
    });

});

/*
|--------------------------------------------------------------------------
| Singleplayer resources
|--------------------------------------------------------------------------
*/
Route::prefix('/singleplayer/')->group(function () {
    Route::get('', function () {
        return view('singleplayer.home');
    })->name('singleplayer.home');
});

/*
|--------------------------------------------------------------------------
| Multiplayer Resources
|--------------------------------------------------------------------------
*/
Route::prefix('/multiplayer/')->group(function () {

    // ----------------------------- Stuff that only requires a login
    Route::group(['middleware' => ['auth']], function () {
        Route::get('characters', [CharacterController::class, 'getCharacters'])->name('multiplayer.characters');
        Route::post('character', [CharacterController::class, 'setActiveCharacter'])->name('multiplayer.character.set');
        Route::get('characterrequired', [CharacterController::class, 'showCharacterRequired'])->name('multiplayer.character.required');
    });

    // ----------------------------- Stuff that doesn't require a character
    Route::group(['middleware' => ['auth', 'not.locked', 'verified', 'tos.agreed']], function () {
        Route::get('', [MultiplayerController::class, 'showHome'])->name('multiplayer.home');

        // Character generation
        Route::get('charactergeneration', [CharacterController::class, 'showCharacterGeneration'])->name('multiplayer.character.generate');

        // Character password recovery
        Route::get('changecharacterpassword', [CharacterController::class, 'showChangeCharacterPassword'])->name('multiplayer.character.changepassword');
        Route::post('changecharacterpassword', [CharacterController::class, 'changeCharacterPassword']);

    });

    // ----------------------------- Stuff that requires a character set and approved
    Route::group(['middleware' => ['auth', 'not.locked', 'verified', 'tos.agreed', 'character']], function () {

        // Character Editing
        Route::get('character', [CharacterController::class, 'showCharacterHub'])->name('multiplayer.character');
        Route::get('avatar', [MultiplayerController::class, 'showPending'])->name('multiplayer.avatar.edit');
        Route::get('perks', [MultiplayerController::class, 'showPending'])->name('multiplayer.perks');
        Route::get('quirks', [MultiplayerController::class, 'showPending'])->name('multiplayer.quirks');
        Route::get('perknotes', [MultiplayerController::class, 'showPending'])->name('multiplayer.perknotes');
        Route::get('classes', [MultiplayerController::class, 'showPending'])->name('multiplayer.classes');
        Route::get('professions', [MultiplayerController::class, 'showPending'])->name('multiplayer.professions');
        Route::get('training', [MultiplayerController::class, 'showPending'])->name('multiplayer.training');
        Route::get('kinks', [MultiplayerController::class, 'showPending'])->name('multiplayer.kinks');
        Route::get('dedication', [MultiplayerController::class, 'showPending'])->name('multiplayer.dedication');
        Route::get('ai', [MultiplayerController::class, 'showPending'])->name('multiplayer.ai');

        Route::get('forms', [MultiplayerController::class, 'showPending'])->name('multiplayer.forms');
        Route::get('inventory', [MultiplayerController::class, 'showPending'])->name('multiplayer.inventory');

    });

});
