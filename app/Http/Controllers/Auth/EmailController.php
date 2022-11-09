<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    public function resendVerificationEmail(Request $request) : RedirectResponse
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
     * @param Request $request
     * @return RedirectResponse
     */
    public function verifyEmail(Request $request): RedirectResponse
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
}
