<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\VerifyEmail;
use App\User as User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function showAccount(): View
    {
        return view('account');
    }

    public function findIssuesWithPassword(string $password): array
    {
        $issues = [];
        if (strlen($password) < 3) $issues[] = 'Password is too short (minimum width is 3 characters)';
        if (preg_match("/\s/", $password)) $issues[] = 'Password can not contain spaces.';
        if (preg_match("/[^\x20-\x7E]/", $password)) $issues[] = 'Password can only contain characters representable by ANSI.';
        return $issues;
    }

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

        event(new Registered($user));

        $remember = $request->has('forget') ? !$request['forget'] : true;
        auth()->guard()->login($user, $remember);

        event(new Login(auth()->guard()::class, $user, $remember));

        //TODO: Consider moving notification to being queued
        $user->notify(new VerifyEmail());

        // Set referral on new account if one is in the session
        if ($request->session()->has('account.referral')) {
            $user->setAccountProperty('tutor', $request->session()->get('account.referral'));
        }

        return redirect()->intended(route('welcome'));
    }

}
