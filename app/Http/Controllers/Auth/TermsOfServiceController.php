<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class TermsOfServiceController extends Controller
{
    public function showTermsOfService(){
        return view('auth.terms-of-service');
    }

}
