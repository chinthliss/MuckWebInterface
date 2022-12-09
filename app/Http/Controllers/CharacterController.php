<?php

namespace App\Http\Controllers;

use App\Muck\MuckObjectService;
use App\User as User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CharacterController extends Controller
{
    public function getCharacters(): array
    {
        /** @var User $user */
        $user = auth()->guard()->user();
        if (!$user) abort(401);

        return array_map(function ($character) {
            return $character->toPlayerArray();
        }, $user->getCharacters());
    }

    public function setActiveCharacter(Request $request, MuckObjectService $muckObjectService): array|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (!$user) abort(401);

        $dbref = $request->get('dbref');
        if (!$dbref) abort(400);

        $character = $muckObjectService->getByDbref($dbref);
        if (!$character || $character->accountId() != $user->id()) abort(403);

        // This is sufficient, middleware will set the cookie in the response
        $user->setCharacter($character);
        // Redirect to an intended page if set, otherwise default to present page.
        $redirect = redirect()->intended(redirect()->back()->getTargetUrl());
        if ($request->expectsJson()) {
            return [
                'result' => 'OK',
                'redirect' => $redirect->getTargetUrl()
            ];
        } else return $redirect;
    }

    public function showCharacterEdit()
    {
        return view('multiplayer.character-edit');
    }

    public function showCharacterGeneration()
    {
        return view('multiplayer.character-generation');
    }

    public function showCharacterRequired()
    {
        return view('multiplayer.character-required');
    }

}
