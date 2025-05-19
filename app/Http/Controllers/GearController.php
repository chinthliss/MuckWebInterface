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

    public function showInventory(): View
    {
        return view('pending');
    }

    public function showCrafting(): View
    {
        return view('multiplayer.gear-crafting');
    }
}
