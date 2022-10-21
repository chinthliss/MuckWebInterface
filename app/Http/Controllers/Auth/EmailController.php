<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class EmailController extends Controller
{
    public function showVerifyEmail() {
        return view('auth.email-verify');
    }
}
