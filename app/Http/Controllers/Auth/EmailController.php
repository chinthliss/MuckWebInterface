<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmailController extends Controller
{
    public function showVerifyEmail(): View|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        if ($user->hasVerifiedEmail()) return redirect(route('welcome'));

        return view('auth.email-verify');
    }

    public function resendVerificationEmail(): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            return redirect(route('welcome'));
        }

        $user->sendEmailVerificationNotification();

        return back()->with('resent', true);
    }

    /**
     * Should be protected by the 'signed' middleware!
     * @return RedirectResponse
     */
    public function verifyEmail(): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) return redirect(route('welcome'));

        Log::info("AUTH Email verified for $user");
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect(route('welcome'));
    }

    public function showChangeEmail(): View
    {
        return view('auth.email-change');
    }

    public function changeEmail(Request $request): View
    {
        /** @var User $user */
        $user = auth()->user();
        Log::Info("AUTH Received email change request for $user");
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ], [
            'email.required' => "Please enter the new email you wish to use.",
            'password.required' => "Please enter your existing password to authorize the change of email."
        ]);
        if (!auth()->guard()->getProvider()->validateCredentials($user, ['password' => $request['password']])) {
            throw ValidationException::withMessages(['password' => ["Password provided doesn't match existing password"]]);
        }

        Log::Info("AUTH Accepted email change request for $user");
        $user->setEmail($request['email']);
        $user->sendEmailVerificationNotification();
        return view('auth.email-change-processed');
    }
}
