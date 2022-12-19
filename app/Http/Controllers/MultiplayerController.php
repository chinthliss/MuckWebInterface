<?php

namespace App\Http\Controllers;

use App\User as User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MultiplayerController extends Controller
{
    public function showHome(Request $request): View
    {
        return view('multiplayer.home');
    }

    public function showPending()
    {
        return view ('multiplayer.pending');
    }

}
