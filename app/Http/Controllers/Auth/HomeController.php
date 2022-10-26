<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User as User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function showWelcome(): View
    {
        return view('welcome');
    }

    public function showLocked(): View|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->guard()->user();
        if (!$user || !$user->getLockedAt()) return redirect()->route('welcome');

        return view('auth.locked');
    }


}
