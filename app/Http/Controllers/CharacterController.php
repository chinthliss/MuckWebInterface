<?php

namespace App\Http\Controllers;

use App\Muck\MuckObjectService;
use App\Muck\MuckService;
use App\User as User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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

    public function showCharacterHub(): View
    {
        return view('multiplayer.character-hub');
    }

    public function showCharacterGeneration(): View
    {
        return view('multiplayer.character-generation');
    }

    public function showCharacterRequired(): View
    {
        return view('multiplayer.character-required');
    }

    public function showChangeCharacterPassword(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $characters = [];
        foreach ($user->getCharacters() as $character) {
            $characters[] = $character->toArray();
        }
        return view('multiplayer.character-password-change', ['characters' => $characters]);
    }

    /**
     * @param Request $request
     * @param MuckService $muck
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function changeCharacterPassword(Request $request, MuckService $muck): RedirectResponse
    {
        $request->validate([
            'account_password' => 'required',
            'character' => 'required',
            'character_password' => 'required'
        ], [
            'account_password.required' => 'You need to enter your Account Password.',
            'character.required' => 'You need to select a character.',
            'character_password.required' => 'You need to enter a new password to use.'
        ]);

        /** @var User $user */
        $user = auth()->user();

        if (!auth()->guard()->getProvider()->validateCredentials($user, ['password' => $request['account_password']])) {
            throw ValidationException::withMessages(['account_password' => ["The provided password for the account was incorrect."]]);
        }

        $characters = $user->getCharacters();
        $character = null;
        foreach ($characters as $possible) {
            if ($possible->dbref == $request['character']) $character = $possible;
        }
        if (!$character) {
            throw ValidationException::withMessages(['character' => ["The provided character was incorrect."]]);
        }

        $passwordIssues = $muck->findProblemsWithCharacterPassword($request['character_password']);
        if ($passwordIssues) throw ValidationException::withMessages(['character_password' => $passwordIssues]);

        Log::debug("Character Password Change: $character being changed by $user");
        $result = $muck->changeCharacterPassword($user, $character, $request['character_password']);
        if ($result) {
            $request->session()->flash('message-success', "The password for {$character->name} was changed as requested. You can now use this password to logon via a telnet client.");
            return redirect(route('multiplayer.home'));
        } else throw ValidationException::withMessages(['character' => ["Something went wrong, if this continues please notify staff."]]);
    }


}
