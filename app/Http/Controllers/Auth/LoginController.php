<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Utility class to return the auth guard
     */
    public function guard()
    {
        return auth()->guard();
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function loginAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|max:255',
            'password' => 'required|max:255'
        ]);

        $remember = $request->has('forget') ? !$request['forget'] : true;

        $attemptResult = $this->guard()->attempt($request->only('email', 'password'), $remember);
        if ($attemptResult) {
            $request->session()->regenerate();
            $user = $this->guard()->user();
            event(new Login($this->guard()::class, $user, $remember));

            //TODO: Look better at implementing loginThrottle
            // $this->clearLoginAttempts($request);

            //TODO: Remove test message in login
            $request->session()->flash('message-success', 'You have logged in! (And this is a test message.)');

            return redirect()->intended(route('multiplayer.home'));
        } else {
            $user = $this->guard()->getProvider()->retrieveByCredentials($request->only('email'));
            event(new Failed($this->guard()::class, $user, $request->only('email', 'password')));
            throw ValidationException::withMessages(['password' => ['Unrecognized Email/Password or Character/Password combination.']]);
        }

    }

    public function logoutAccount(Request $request): RedirectResponse
    {
        $user = $this->guard()->user();
        $this->guard()->logout();
        $request->session()->invalidate();
        event(new Logout($this->guard()::class, $user));
        return redirect()->route('auth.login');
    }

    public function showForgottenPassword(): View
    {
        return view('auth.password-forgotten');
    }

}
