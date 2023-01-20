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
        $identifier = $request['email'];
        $credentials = $request->only('password');

        // The given email can also be a character, in which case we fix the label going forwards
        if (strpos($request['email'], '@')) {
            $credentials['email'] = $identifier;
        } else {
            $credentials['character'] = $identifier;
        }

        $attemptResult = auth()->guard()->attempt($credentials, $remember);
        if ($attemptResult) {
            $request->session()->regenerate();
            $user = auth()->guard()->user();
            Log::info("AUTH Successful Login for $identifier as: $user");

            // Fired by Laravel
            // event(new Login(auth()->guard()::class, $user, $remember));

            //TODO: Look better at implementing loginThrottle
            // $this->clearLoginAttempts($request);

            return redirect()->intended(route('welcome'));
        } else {
            $user = auth()->guard()->getProvider()->retrieveByCredentials($credentials);
            Log::info("AUTH Failed Login for $identifier as: " . ($user ?: '-Unrecognized-'));
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
        Log::info("AUTH $user logged out");
        // Dispatched by Laravel
        // event(new Logout(auth()->guard()::class, $user));
        return redirect()->route('auth.login');
    }

}
