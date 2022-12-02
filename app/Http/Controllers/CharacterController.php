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

    public function setActiveCharacter(Request $request, MuckObjectService $muckObjectService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (!$user) abort(401);

        $dbref = $request->get('dbref');
        if (!$dbref) abort(400);

        $character = $muckObjectService->getByDbref($dbref);
        if ($character && $character->accountId() == $user->id()) {
            // This is sufficient, middleware will set the cookie in the response
            $user->setCharacter($character);
        } else {
            $request->session()->flash('message-success', 'Attempt to change character failed');
        }
        return redirect()->back();

    }

}
