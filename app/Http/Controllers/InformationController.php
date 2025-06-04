<?php

namespace App\Http\Controllers;

use App\User as User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InformationController extends Controller
{


    public function showInformationHub(): View
    {
        return view('multiplayer.info');
    }


    public function showFormBrowser(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $character = ($user?->getCharacter());

        return view('multiplayer.info-form-browser')->with([
            'startingPlayerName' => $character?->name,
            'staff' => $character?->isStaff()
        ]);
    }

    public function showStatusBrowser(): View
    {
        return view('multiplayer.info-status-browser');
    }

}
