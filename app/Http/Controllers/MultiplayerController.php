<?php

namespace App\Http\Controllers;

use App\User as User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Log;

class MultiplayerController extends Controller
{
    public function showHome(Request $request): View | RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $charactersToProcess = $user->getCharacters();
        if (count($charactersToProcess) === 0) //Redirect to getting started if we have no characters
            return redirect(route('multiplayer.guide.starting'));

        return view('multiplayer.home');
    }

    public function showGettingStarted() : View
    {
        /** @var User $user */
        $user = auth()->user();
        $character = ($user?->getCharacter());

        return view('multiplayer.getting-started')->with([
            'hasAccount' => $user !== null,
            'hasAnyCharacter' => ($user && count($user->getCharacters()) > 0),
            'hasActiveCharacter' => $character !== null,
            'hasApprovedCharacter' => ($character && $character->approved()),
            'pageRecommendations' => [
                [
                    'page' => 'Perks',
                    'description' => "After character generation there are many additional perks you can pick up.",
                    'url' => route('multiplayer.perks')
                ],
                [
                    'page' => 'Avatar',
                    'description' => "Control how your character appears to others on the website.",
                    'url' => route('multiplayer.avatar.edit')
                ],
                [
                    'page' => 'Kinks',
                    'description' => "Set your preferences so others know how to interact with you.",
                    'url' => route('multiplayer.kinks')
                ]
            ]
        ]);
    }

    public function showHelp(?string $startingPage = null): View
    {
        return view('multiplayer.help')->with([
            'startingPage' => $startingPage
        ]);
    }

    public function showFormBrowser(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $character = ($user?->getCharacter());

        return view('multiplayer.form-browser')->with([
            'startingPlayerName' => $character?->name,
            'staff' => $character?->isStaff()
        ]);
    }

}
