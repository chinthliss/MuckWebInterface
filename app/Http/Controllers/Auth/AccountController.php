<?php

namespace App\Http\Controllers\Auth;

use App\AccountHistoryManager;
use App\AccountNotificationManager;
use App\Http\Controllers\Controller;
use App\User as User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function showAccount(): View
    {
        /** @var User $user */
        $user = auth()->user();
        return view('account', [
            'account' => $user->toArray('user')
        ]);
    }

    public function showSettings(): View
    {
        /** @var User $user */
        $user = auth()->user();
        return view('settings', [
            'settings' => [
                'avatarPreference' => $user->getAvatarPreference(),
            ]
        ]);
    }

    /**
     * Returns the setting name that was changed if successful
     */
    public function setAccountSetting(Request $request): string
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$request->has('setting')) abort(400);
        $setting = $request->get('setting');

        if (!$request->has('value')) abort(400);
        $value = $request->get('value');

        switch ($setting) {
            case 'avatarPreference':
                $user->setAvatarPreferenceFromString($value);
                break;
            default:
                abort(400, 'Unrecognized setting requested.');
        }
        return $setting;
    }

    public function findIssuesWithPassword(string $password): array
    {
        $issues = [];
        if (strlen($password) < 3) $issues[] = 'Password is too short (minimum width is 3 characters)';
        if (preg_match("/\s/", $password)) $issues[] = 'Password can not contain spaces.';
        if (preg_match("/[^\x20-\x7E]/", $password)) $issues[] = 'Password can only contain characters representable by ANSI.';
        return $issues;
    }

    /**
     * @throws ValidationException
     */
    public function createAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|max:255',
            'password' => 'required|max:255'
        ]);

        if (!auth()->guard()->getProvider()->isEmailAvailable($request['email'])) {
            throw ValidationException::withMessages(['email' => ['This email is already in use.']]);
        }

        if ($passwordCheck = $this->findIssuesWithPassword($request['password'])) {
            throw ValidationException::withMessages(['password' => $passwordCheck]);
        }

        /** @var User $user */
        $user = auth()->guard()->getProvider()->createUser($request['email'], $request['password']);

        Log::info("AUTH Created $user with email " . $request['email']);
        event(new Registered($user));

        $remember = $request->has('forget') ? !$request['forget'] : true;
        auth()->guard()->login($user, $remember);

        // Fired by Laravel
        // event(new Login(auth()->guard()::class, $user, $remember));

        // Laravel is sending this in reaction to its own events
        // $user->sendEmailVerificationNotification();

        // Set referral on new account if one is in the session
        if ($request->session()->has('account.referral')) {
            $user->setAccountProperty('tutor', $request->session()->get('account.referral'));
        }

        return redirect()->intended(route('welcome'));
    }

    public function showDeleteAccount(): View
    {
        return view('account.delete');
    }

    #region Account Notifications
    // Presented as just 'notifications' to the user.
    // Named AccountNotifications internally to avoid collision with other potential classes

    public function showNotifications(): View
    {
        return view('notifications');
    }

    public function showAccountCurrencyHistory(AccountHistoryManager $historyManager): View
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) abort(401);
        return view('account.history')->with([
            'history' => $historyManager->getHistoryFor($user)
        ]);
    }

    public function getNotifications(AccountNotificationManager $notificationManager): array
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) abort(401);

        return array_map(function ($notification) {
            if ($notification['character']) $notification['character'] = $notification['character']->name;
            return $notification;
        }, $notificationManager->getNotificationsFor($user));
    }

    public function deleteNotification(AccountNotificationManager $notificationManager, int $id): void
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$user) abort(401);

        $notification = $notificationManager->getNotification($id);

        if (!$notification) abort(404); // Maybe already deleted

        if ($notification->aid != $user->id()) abort(401);

        $notificationManager->deleteNotification($id);

    }

    public function deleteAllNotifications(AccountNotificationManager $notificationManager, Request $request): void
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$user) abort(401);

        if (!$request->has('highestId')) abort(400);
        $highestId = $request->get('highestId');

        $notificationManager->deleteAllNotificationsFor($user, $highestId);
    }

    #endregion Account Notifications

}
