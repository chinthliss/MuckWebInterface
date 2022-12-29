<?php

namespace App\Http\Controllers;

use App\User as User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function showHome(Request $request): View
    {
        return view('admin.home');
    }
}
