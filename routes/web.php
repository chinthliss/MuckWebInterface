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
use App\Http\Controllers\Payment\CardManagementController;
use App\Http\Controllers\Payment\AccountCurrencyController;
use App\Http\Controllers\AvatarController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core resources that are always available
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'showWelcome'])
    ->name('welcome');
Route::get('termsofservice', [TermsOfServiceController::class, 'showTermsOfService'])
    ->name('auth.terms-of-service');
Route::get('accountlocked', [HomeController::class, 'showLocked'])
    ->name('auth.locked');

//Character Avatar related images (Image resources that skip middleware)
Route::withoutMiddleware('web')->group(function () {
    Route::get('a/{name}', [AvatarController::class, 'getAvatarFromCharacterName'])
        ->name('avatar.render');
    Route::get('avatar/gradient/{name}', [AvatarController::class, 'getGradient'])
        ->name('avatar.gradient.render');
    Route::get('avatar/gradient/preview/{code?}', [AvatarController::class, 'getGradientPreview'])
        ->name('avatar.gradient.preview');
    Route::get('avatar/item/{id}', [AvatarController::class, 'getAvatarItem'])
        ->name('avatar.item.render');
    Route::get('avatar/itempreview/{id}', [AvatarController::class, 'getAvatarItemPreview'])
        ->name('avatar.item.preview');
});

// Websocket authentication
Route::get('auth/websocketToken', [LoginController::class, 'getWebsocketToken']);

/*
|--------------------------------------------------------------------------
| Core resources that are available only when NOT logged in
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['guest']], function () {
    Route::get('login', [LoginController::class, 'showLogin'])
        ->name('auth.login');
    Route::post('login', [LoginController::class, 'loginAccount'])->middleware('throttle:8,1');
    Route::post('createaccount', [AccountController::class, 'createAccount'])
        ->name('auth.create');

    //Password forgot / reset
    Route::get('forgotpassword', [PasswordController::class, 'showForgottenPassword'])
        ->name('auth.password.forgot');
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
    Route::post('logout', [LoginController::class, 'logoutAccount'])
        ->name('auth.logout');
    Route::get('verifyemail', [EmailController::class, 'showVerifyEmail'])
        ->name('auth.email.verify');
    Route::get('verifyemail/{id}/{hash}', [EmailController::class, 'verifyEmail'])
        ->name('auth.email.verification')->middleware('signed', 'throttle:8,1');
    Route::get('resendverifyemail', [EmailController::class, 'resendVerificationEmail'])
        ->name('auth.email.resendVerification');
    Route::post('termsofservice', [TermsOfServiceController::class, 'acceptTermsOfService']);
    Route::get('account', [AccountController::class, 'showAccount'])
        ->name('account');
    Route::get('changepassword', [PasswordController::class, 'showChangePassword'])
        ->name('auth.password.change');
    Route::post('changepassword', [PasswordController::class, 'changePassword']);
    Route::get('newemail', [EmailController::class, 'showNewEmail'])
        ->name('auth.email.new');
    Route::post('newemail', [EmailController::class, 'newEmail']);
    Route::post('changeemail', [EmailController::class, 'changeEmail'])
        ->name('auth.email.change');

    // Preference change
    Route::post('account/updateAvatarPreference', [AccountController::class, 'updateAvatarPreference'])
        ->name('account.avatar.preference');

    // Notifications
    Route::get('notifications', [AccountController::class, 'showNotifications'])
        ->name('notifications');
    Route::get('notifications.api', [AccountController::class, 'getNotifications'])
        ->name('notifications.api');
    Route::delete('notifications.api/{id}', [AccountController::class, 'deleteNotification']);
    Route::delete('notifications.api', [AccountController::class, 'deleteAllNotifications']);

    // Account Transactions
    Route::get('account/transactions', [AccountCurrencyController::class, 'showTransactions'])
        ->name('account.transactions');
    Route::get('account/transactions/{id}', [AccountCurrencyController::class, 'showTransaction'])
        ->name('account.transaction');
    Route::post('account/transactions.accept', [AccountCurrencyController::class, 'acceptTransaction'])
        ->name('account.transaction.accept');
    Route::post('account/transactions.decline', [AccountCurrencyController::class, 'declineTransaction'])
        ->name('account.transaction.decline');
    Route::post('account/transaction/card', [AccountCurrencyController::class, 'newCardTransaction'])
        ->name('account.transaction.new.card');
    Route::post('account/transaction/paypal', [AccountCurrencyController::class, 'newPayPalTransaction'])
        ->name('account.transaction.new.paypal');

    // Account Subscriptions
    // The actual list of subscriptions is shown via the account screen
    Route::post('account/subscription/newCardSubscription', [AccountCurrencyController::class, 'newCardSubscription'])
        ->name('account.subscription.new.card');
    Route::post('account/subscription/newPayPalSubscription', [AccountCurrencyController::class, 'newPayPalSubscription'])
        ->name('account.subscription.new.paypal');
    Route::get('account/subscriptions/{id}', [AccountCurrencyController::class, 'showSubscription'])
        ->name('account.subscription');
    Route::post('account/subscription.accept', [AccountCurrencyController::class, 'acceptSubscription'])
        ->name('account.subscription.accept');
    Route::post('account/subscription.decline', [AccountCurrencyController::class, 'declineSubscription'])
        ->name('account.subscription.decline');
    Route::post('account/subscription.cancel', [AccountCurrencyController::class, 'cancelSubscription'])
        ->name('account.subscription.cancel');

    // Card Management
    Route::get('account/cardmanagement', [CardManagementController::class, 'show'])
        ->name('payment.cardmanagement');
    Route::post('account/cardmanagement.api', [CardManagementController::class, 'addCard'])
        ->name('payment.cardmanagement.add');
    Route::delete('account/cardmanagement.api', [CardManagementController::class, 'deleteCard'])
        ->name('payment.cardmanagement.delete');
    Route::patch('account/cardmanagement.api', [CardManagementController::class, 'setDefaultCard'])
        ->name('payment.cardmanagement.setdefault');
});

/*
|--------------------------------------------------------------------------
| Core resources that require some sort of administrative power
|--------------------------------------------------------------------------
*/
Route::prefix('/admin/')->group(function () {

    // ----------------------------- Staff level
    Route::group(['middleware' => ['auth', 'role:staff']], function () {
        Route::get('', [AdminController::class, 'showHome'])
            ->name('admin.home');
        Route::get('tickets', [HomeController::class, 'showPending'])
            ->name('admin.tickets');

        //Avatar Doll testing
        Route::prefix('avatar/')->group(function () {
            Route::get('dolllist', [AvatarController::class, 'showAdminDollList'])
                ->name('admin.avatar.dolllist');
            Route::get('dolltest/{code?}', [AvatarController::class, 'showAdminDollTest'])
                ->name('admin.avatar.dolltest');
            Route::get('dolltest/{dollName}/thumbnail', [AvatarController::class, 'getThumbnailForDoll'])
                ->name('admin.avatar.dollthumbnail');
            Route::get('render/{code?}', [AvatarController::class, 'getAvatarFromAdminCode'])
                ->name('admin.avatar.render');
            Route::get('testall', [AvatarController::class, 'getAllAvatarsAsAGif']);
        });

        //Avatar Gradients
        Route::get('avatar/gradients', [AvatarController::class, 'showAdminAvatarGradients'])
            ->name('admin.avatar.gradients');

        //Avatar Items
        Route::get('avatar/items', [AvatarController::class, 'showAdminAvatarItems'])
            ->name('admin.avatar.items');
    });

    // ----------------------------- Admin level
    Route::group(['middleware' => ['auth', 'role:admin']], function () {

        Route::get('accounts', [AdminController::class, 'showAccountBrowser'])
            ->name('admin.accounts');
        Route::get('accounts.api', [AdminController::class, 'findAccounts'])
            ->name('admin.accounts.api');
        Route::get('account/{accountId}', [AdminController::class, 'showAccount'])
            ->name('admin.account');
        Route::post('account/{accountId}', [AdminController::class, 'processAccountChange'])
            ->name('admin.account.api');
    });

    // ----------------------------- Site Admin level
    Route::group(['middleware' => ['auth', 'role:siteadmin']], function () {
        Route::get('transactions', [AccountCurrencyController::class, 'adminShowTransactions'])
            ->name('admin.transactions');
        Route::get('transactions/api', [AccountCurrencyController::class, 'adminGetTransactions'])
            ->name('admin.transactions.api');
        Route::get('transactions/{id}', [AccountCurrencyController::class, 'adminShowTransaction'])
            ->name('admin.transaction');
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

    // ----------------------------- Stuff that is always available

    // Getting started page
    Route::get('gettingstarted', [MultiplayerController::class, 'showGettingStarted'])
        ->name('multiplayer.guide.starting');

    // ----------------------------- Stuff that only requires a login
    Route::group(['middleware' => ['auth']], function () {
        Route::get('characterselect', [CharacterController::class, 'getCharacterSelectState'])
            ->name('multiplayer.character.state');
        Route::post('character', [CharacterController::class, 'setActiveCharacter'])
            ->name('multiplayer.character.set');
        Route::post('buycharacterslot', [CharacterController::class, 'buyCharacterSlot'])
            ->name('multiplayer.character.buyslot');
        Route::get('characterrequired', [CharacterController::class, 'showCharacterRequired'])
            ->name('multiplayer.character.required');
    });

    // ----------------------------- Stuff that doesn't require a character
    Route::group(['middleware' => ['auth', 'not.locked', 'verified', 'tos.agreed']], function () {
        Route::get('', [MultiplayerController::class, 'showHome'])
            ->name('multiplayer.home');

        // Character creation
        Route::get('charactercreate', [CharacterController::class, 'showCreateCharacter'])
            ->name('multiplayer.character.create');
        Route::post('charactercreate', [CharacterController::class, 'createCharacter']);
        // Though it requires a character set, this is here to avoid getting 'You need to complete character generation' messages on accessing it.
        Route::get('character-initial-setup', [CharacterController::class, 'showCharacterInitialSetup'])
            ->name('multiplayer.character.initial-setup');
        Route::post('character-initial-setup', [CharacterController::class, 'finalizeCharacter']);

        // Character password recovery
        Route::get('changecharacterpassword', [CharacterController::class, 'showChangeCharacterPassword'])
            ->name('multiplayer.character.changepassword');
        Route::post('changecharacterpassword', [CharacterController::class, 'changeCharacterPassword']);

    });

    // ----------------------------- Stuff that requires a character set and approved
    Route::group(['middleware' => ['auth', 'not.locked', 'verified', 'tos.agreed', 'character']], function () {

        // Character Editing
        Route::get('character', [CharacterController::class, 'showCharacterHub'])
            ->name('multiplayer.character');
        Route::get('perks', [HomeController::class, 'showPending'])
            ->name('multiplayer.perks');
        Route::get('quirks', [HomeController::class, 'showPending'])
            ->name('multiplayer.quirks');
        Route::get('perknotes', [HomeController::class, 'showPending'])
            ->name('multiplayer.perknotes');
        Route::get('classes', [HomeController::class, 'showPending'])
            ->name('multiplayer.classes');
        Route::get('professions', [HomeController::class, 'showPending'])
            ->name('multiplayer.professions');
        Route::get('training', [HomeController::class, 'showPending'])
            ->name('multiplayer.training');
        Route::get('kinks', [HomeController::class, 'showPending'])
            ->name('multiplayer.kinks');
        Route::get('dedication', [HomeController::class, 'showPending'])
            ->name('multiplayer.dedication');
        Route::get('ai', [HomeController::class, 'showPending'])
            ->name('multiplayer.ai');

        Route::get('forms', [HomeController::class, 'showPending'])
            ->name('multiplayer.forms');
        Route::get('inventory', [HomeController::class, 'showPending'])
            ->name('multiplayer.inventory');

        // Avatar functionality
        Route::get('avatar/gradients', [AvatarController::class, 'showUserAvatarGradients'])
            ->name('multiplayer.avatar.gradients');
        Route::get('avatar', [AvatarController::class, 'showAvatarEditor'])
            ->name('multiplayer.avatar.edit');
        Route::get('avatar/state', [AvatarController::class, 'getAvatarState'])
            ->name('multiplayer.avatar.state');
        Route::post('avatar/state', [AvatarController::class, 'setAvatarState']);
        Route::post('avatar/gradients/buy', [AvatarController::class, 'buyGradient'])
            ->name('multiplayer.avatar.gradient.buy');
        Route::post('avatar/buyitem', [AvatarController::class, 'buyItem'])
            ->name('multiplayer.avatar.item.buy');
        Route::get('avatar/edit/{code?}', [AvatarController::class, 'getAvatarFromUserCode'])
            ->name('multiplayer.avatar.edit.render');

        //Direct Connect
        // TODO: Actual direct connect
        Route::get('multiplayer/connect', [MultiplayerController::class, 'showConnect'])
            ->name('multiplayer.connect');

    });

});
