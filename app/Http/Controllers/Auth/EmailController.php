<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
        if ($request->user()->hasVerifiedEmail()) {
            return redirect(route('welcome'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('resent', true);
    }
}
