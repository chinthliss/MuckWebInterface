<?php

namespace App\Http\Controllers;

use App\Avatar\AvatarService;
use App\Muck\MuckObjectService;
use App\Muck\MuckService;
use App\Notifications\MuckWebInterfaceNotification;
use App\User as User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CharacterController extends Controller
{
    public function getCharacterSelectState(MuckService $muck): array
    {
        /** @var User $user */
        $user = auth()->guard()->user();
        if (!$user) abort(401);

        $characters = array_map(function ($character) {
            return $character->toPlayerArray();
        }, $user->getCharacters());

        $state = $muck->getCharacterSlotStateFor($user);

        return [
            'characters' => $characters,
            'characterSlotCount' => $state['count'],
            'characterSlotCost' => $state['cost']
        ];
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

    public function showCreateCharacter(): View
    {
        return view('multiplayer.character-creation');
    }

    /**
     * @throws ValidationException
     */
    public function createCharacter(Request $request, MuckService $muck): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $request->validate([
            'characterName' => 'required'
        ]);
        $desiredName = $request->input('characterName');
        $issue = $muck->findProblemsWithCharacterName($desiredName);
        if ($issue) throw ValidationException::withMessages(['characterName' => $issue]);

        $result = $muck->createCharacterFor($user, $desiredName);
        if ($result['result'] == 'ERROR') {
            throw ValidationException::withMessages(['characterName' => $result['error']]);
        }
        $user->setCharacter($result['character']);

        MuckWebInterfaceNotification::notifyUser($user, "Your new character '$desiredName' has been created with an initial password of: {$result['initialPassword']}");

        return redirect()->route('multiplayer.character.initial-setup');
    }

    public function showCharacterInitialSetup(MuckService $muck): View | RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();

        //Because this page is available without an approved character, we need to manually handle redirects
        if (!$character) {
            session()->flash('message-success', 'You need to create a character before you can set them up.');
            return redirect(route('multiplayer.character.create'));
        }

        if ($character->approved()) {
            session()->flash('message-success', 'Your character has already completed initial setup!');
            return redirect(route('multiplayer.home'));
        }

        $config = $muck->getCharacterInitialSetupConfigurationFor($user);

        return view('multiplayer.character-initial-setup')->with([
            'config' => $config
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function finalizeCharacter(Request $request, MuckService $muck): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();

        //Because this page is available without an approved character, we need to manually handle redirects
        if (!$character) {
            session()->flash('message-success', 'You need to create a character before you can set them up.');
            return redirect(route('multiplayer.character.create'));
        }

        if ($character->approved()) {
            session()->flash('message-success', 'Your character has already completed initial setup!');
            return redirect(route('multiplayer.home'));
        }

        $request->validate([
            'gender' => 'required',
            'birthday' => 'required',
            'faction' => 'required'
        ], [
            'gender.required' => 'You need to select a starting gender.',
            'birthday.required' => 'You need to select a birthday.',
            'faction.required' => 'You need to select a faction.'
        ]);

        // We're not doing any real validation here - we'd just be duplicating what the muck does

        $characterRequest = [
            'dbref' => $character->dbref,
            'gender' => $request->input('gender'),
            'birthday' => $request->input('birthday'),
            'faction' => $request->input('faction'),
            'perks' => $request->input('perks') ?? [],
            'flaws' => $request->input('flaws') ?? []
        ];
        $response = $muck->finalizeCharacter($characterRequest);

        if ($response['result'] == 'ERROR') {
            throw ValidationException::withMessages(['other' => $response['messages']]);
        }

        return redirect()->route('multiplayer.guide.starting');


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
            $request->session()->flash('message-success', "The password for $character->name was changed as requested. You can now use this password to logon via a telnet client.");
            return redirect(route('multiplayer.home'));
        } else throw ValidationException::withMessages(['character' => ["Something went wrong, if this continues please notify staff."]]);
    }

    public function buyCharacterSlot(MuckService $muck): array
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) abort(401);

        return $muck->buyCharacterSlot($user);
    }

    public function showCharacterProfile(MuckService $muck, string $name): View
    {
        /** @var User $user */
        $user = auth()->user();

        $character = $muck->getByPlayerName($name);
        if (!$character) abort(404);

        $avatarUrl = route('avatar.render', ['name' => $character->name]);
        if ($user && $user->getAvatarPreference() === $user::AVATAR_PREFERENCE_HIDDEN) $avatarUrl = '';

        return view('multiplayer.character-profile')->with([
            'character' => $character,
            'avatarUrl' => $avatarUrl,
            'controls' => $character->accountId() === $user?->id() ? 'true' : 'false',
            'avatarWidth' => AvatarService::DOLL_WIDTH,
            'avatarHeight' => AvatarService::DOLL_HEIGHT
        ]);

    }
}
