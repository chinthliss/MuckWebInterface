<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;

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

    public function showFormEditor(Request $request): View
    {
        return view('multiplayer.contribute-forms')->with([
            'form' => $request->input('form', '')
        ]);
    }
}
