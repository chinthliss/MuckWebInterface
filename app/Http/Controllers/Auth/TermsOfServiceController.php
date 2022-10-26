<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TermsOfServiceController extends Controller
{
    public function showTermsOfService(): View
    {
        return view('auth.terms-of-service');
    }

}
