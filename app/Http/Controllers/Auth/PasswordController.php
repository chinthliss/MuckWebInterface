<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\ResetPassword;
use App\User;
use Error;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function showForgottenPassword(): View
    {
        return view('auth.password-forgotten');
    }

    public function requestPasswordReset(Request $request): RedirectResponse|View
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        /** @var User $user */
        $user = auth()->guard()->getProvider()->retrieveByCredentials($request->only('email'));
        if ($user) {
            Log::info("AUTH Password reset requested for $user");
            $user->notify(new ResetPassword);
        } else {
            Log::info("AUTH Password reset attempted for unknown email: " . $request['email']);
        }
        //Show view either way
        return view('auth.password-reset-sent');
    }

    public function showPasswordReset(): View
    {
        return view('auth.password-reset');
    }

    public function resetPassword(Request $request, AccountController $accountController, $id): RedirectResponse|View
    {
        $request->validate([
            'password' => 'required|confirmed|max:255'
        ]);
        if ($passwordCheck = $accountController->findIssuesWithPassword($request['password'])) {
            throw ValidationException::withMessages(['password'=>$passwordCheck]);
        }
        $user = auth()->guard()->getProvider()->retrieveById($id);
        $user->setPassword($request['password']);
        Log::info("AUTH $user reset their password.");
        event(new PasswordReset($user));
        //TODO: Maybe log in user after successful password reset
        //TODO: Look for other things to do on password change - such as change remember_me
        return view('auth.password-reset-processed');
    }
}
