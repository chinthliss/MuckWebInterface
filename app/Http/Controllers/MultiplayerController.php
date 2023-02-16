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

    public function showGettingStarted() : View
    {
        /** @var User $user */
        $user = auth()->user();
        $character = ($user ? $user->getCharacter() : null);

        return view('multiplayer.getting-started')->with([
            'hasAccount' => $user !== null,
            'hasAnyCharacter' => ($user && count($user->getCharacters()) > 0),
            'hasActiveCharacter' => $character !== null,
            'hasApprovedCharacter' => ($character && $character->approved()),
            // TODO: Wire up links in showGettingStarted
            'pageRecommendations' => [
                [
                    'page' => 'Perks',
                    'description' => "After character generation there are a load of additional perks you can pick up.",
                    'url' => 'TBC'
                ],
                [
                    'page' => 'Avatar',
                    'description' => "Control how your character appears to others on the website.",
                    'url' => 'TBC'
                ],
                [
                    'page' => 'Kinks',
                    'description' => "Set your preferences so others know how to interact with you.",
                    'url' => 'TBC'
                ]
            ]
        ]);
    }

}
