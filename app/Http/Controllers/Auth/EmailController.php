<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\VerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailController extends Controller
{
    public function showVerifyEmail(): View
    {
        return view('auth.email-verify');
    }

    public function resendVerificationEmail(Request $request) : RedirectResponse
    {
        if ($request->user()->getEmailVerifiedAt()) {
            return redirect(route('welcome'));
        }

        //TODO: Consider queuing email
        $request->user()->notify(new VerifyEmail());

        return back()->with('resent', true);
    }
}
