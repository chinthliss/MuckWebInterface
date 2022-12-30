<?php

namespace App\Http\Controllers;

use App\User as User;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function showHome(): View
    {
        return view('admin.home');
    }

    public function showAccountBrowser(): View
    {
        return view('admin.accounts');
    }

    public function lookupAccounts(): array
    {
        return [];
    }
}
