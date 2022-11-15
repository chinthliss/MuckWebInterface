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
            throw ValidationException::withMessages(['password' => $passwordCheck]);
        }
        $user = auth()->guard()->getProvider()->retrieveById($id);
        $user->setPassword($request['password']);
        Log::info("AUTH $user reset their password.");
        event(new PasswordReset($user));
        //TODO: Maybe log in user after successful password reset
        //TODO: Look for other things to do on password change - such as change remember_me
        return view('auth.password-reset-processed');
    }

    public function showChangePassword(): View
    {
        return view('auth.password-change');
    }

    public function changePassword(Request $request, AccountController $accountController)
    {
        $user = auth()->user();
        Log::Info("AUTH Received password change request for $user");
        $request->validate([
            'oldpassword' => 'required',
            'password_confirmation' => 'required',
            'password' => 'required|confirmed|max:255|different:oldpassword'
        ], [
            'oldpassword.required' => "Please enter your existing password.",
            'password_confirmation.required' => "Please confirm the new password you want to use by re-entering it.",
            'password.required' => "Please enter the new password you want to use.",
            'password.confirmation' => "The new password entered did not match the one entered for confirmation.",
            'password.different' => "The new password can't match your existing password"
        ]);
        $user = auth()->user();
        if (!auth()->guard()->getProvider()->validateCredentials($user, ['password' => $request['oldpassword']])) {
            throw ValidationException::withMessages(['oldpassword' => ["Password provided doesn't match existing password"]]);
        }
        if ($passwordCheck = $accountController->findIssuesWithPassword($request['password'])) {
            throw ValidationException::withMessages(['password' => $passwordCheck]);
        }

        Log::Info("AUTH Accepted password change request for $user");
        $user->setPassword($request['password']);
        event(new PasswordReset($user));
        return view('auth.password-change-processed');
    }
}
