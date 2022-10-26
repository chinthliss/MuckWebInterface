<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class EmailController extends Controller
{
    public function showVerifyEmail(): View
    {
        return view('auth.email-verify');
    }
}
