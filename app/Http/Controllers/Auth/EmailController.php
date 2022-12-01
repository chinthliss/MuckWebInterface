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

    /**
     * Used for making an already existing/registered email into the primary one
     * Called by a hidden form, so no feedback
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function changeEmail(Request $request): View | RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $email = $request['email'];

        if (!$email) {
            Log::error("AUTH Email change request for $user didn't have an email specified!");
            return redirect(route('welcome'));
        }

        Log::Info("AUTH Received request to change primary email for $user to $email");

        $emailOwner = User::findByEmail($email, true);

        if (!$user->is($emailOwner)) {
            Log::error("AUTH Email change request for $user was to an email that either doesn't exist or is owned by someone else!");
            return redirect(route('welcome'));
        }

        Log::Info("AUTH Accepted email change request for $user");
        $user->setEmail($email);
        //TODO: Handle not needing to verify an email
        $user->sendEmailVerificationNotification();
        return view('auth.email-change-processed', ['verificationRequired' => true]);
    }

    public function showNewEmail(): View
    {
        return view('auth.email-new');
    }

    public function newEmail(Request $request): View
    {
        /** @var User $user */
        $user = auth()->user();
        Log::Info("AUTH Received request to change to new email for $user");
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

        if (User::findByEmail($request['email'], true)) {
            throw ValidationException::withMessages(['email' => ["That email is already in use. If you believe this to be in error then please raise a support ticket."]]);
        }

        Log::Info("AUTH Accepted new email request for $user");
        $user->setEmail($request['email']);
        $user->sendEmailVerificationNotification();
        return view('auth.email-change-processed', ['verificationRequired' => true]);
    }
}
