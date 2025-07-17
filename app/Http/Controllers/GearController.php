<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;

class GearController extends Controller
{


    public function showGearHub(): View
    {
        return view('multiplayer.gear-hub');
    }

    public function showSalvageMarket(): View
    {
        return view('multiplayer.gear-salvagemarket');
    }

    public function showCrafting(): View
    {
        return view('multiplayer.gear-crafting');
    }
}
