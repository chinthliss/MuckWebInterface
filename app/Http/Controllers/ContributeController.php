<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ContributeController extends Controller
{


    public function showContributeHub(): View
    {
        return view('multiplayer.contribute-hub');
    }

    public function showStringparsingScratchpad(): View
    {
        return view('multiplayer.contribute-stringparsing-scratchpad');
    }

    public function showFormEditor(): View
    {
        return view('multiplayer.contribute-forms');
    }
}
