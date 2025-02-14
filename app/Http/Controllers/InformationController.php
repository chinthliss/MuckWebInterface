<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class InformationController extends Controller
{


    public function showInformationHub(): View
    {
        return view('multiplayer.info');
    }

    public function showStatuses(): View
    {
        return view('multiplayer.info-statuses');
    }

}
