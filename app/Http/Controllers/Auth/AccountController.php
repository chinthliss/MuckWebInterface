<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function showAccount(): View
    {
        return view('account');
    }

    public function findIssuesWithPassword(string $password)
    {
        $issues = array();
        if (strlen($password) < 3) array_push($issues, 'Password is too short (minimum width is 3 characters)');
        if (preg_match("/[\s]/", $password)) array_push($issues, 'Password can not contain spaces.');
        if (preg_match("/[^\x20-\x7E]/", $password)) array_push($issues, 'Password can only contain characters representable by ANSI.');
        return $issues;
    }

}
