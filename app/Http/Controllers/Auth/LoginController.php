<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
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

        $attemptResult = auth()->guard()->attempt($request->only('email', 'password'), $remember);
        if ($attemptResult) {

            $request->session()->regenerate();
            $user = auth()->guard()->user();
            Log::debug("Successful Login by " . $request['email'] . " as: $user");

            // Fired by Laravel
            // event(new Login(auth()->guard()::class, $user, $remember));

            //TODO: Look better at implementing loginThrottle
            // $this->clearLoginAttempts($request);

            //TODO: Remove test message in login
            $request->session()->flash('message-success', 'You have logged in! (And this is a test message.)');

            return redirect()->intended(route('welcome'));
        } else {
            $user = auth()->guard()->getProvider()->retrieveByCredentials($request->only('email'));
            Log::debug("Failed Login by " . $request['email'] . " as: $user");
            // Fired by Laravel
            // event(new Failed(auth()->guard()::class, $user, $request->only('email', 'password')));
            throw ValidationException::withMessages(['login' => ['Unrecognized Email/Password or Character/Password combination.']]);
        }
    }

    public function logoutAccount(Request $request): RedirectResponse
    {
        $user = auth()->guard()->user();
        auth()->guard()->logout();
        $request->session()->invalidate();
        // Dispatched by Laravel
        // event(new Logout(auth()->guard()::class, $user));
        return redirect()->route('auth.login');
    }

}
