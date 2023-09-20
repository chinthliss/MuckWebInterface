<?php

namespace App\Http\Middleware;

use App\Muck\MuckDbref;
use App\Muck\MuckObjectService;
use App\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;


/**
 * Handles the loading a character from a request.
 * These are different middleware because of ordering issues, if in the same file the cookie payload doesn't encrypt.
 */
class LoadActiveCharacter
{
    protected MuckObjectService $muckObjectService;

    public function __construct(MuckObjectService $muckObjectService)
    {
        $this->muckObjectService = $muckObjectService;
    }


    /**
     * If a character dbref is specified, verifies and sets active character on the User object
     * Takes it from the header or cookie with the former getting precedence.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var User $user */
        $user = $request->user();
        if ($user) {
            // Header is the definitive place to have the character specified
            $characterDbref = $request->header('X-Character-Dbref');

            // But the cookie is used on initial set and to keep *something* between sessions
            if (!$characterDbref) $characterDbref = $request->cookie('character-dbref');

            $characterDbref = intval($characterDbref);
            if ($characterDbref) {
                /** @var MuckDbref $character */
                $character = $this->muckObjectService->getByDbref($characterDbref);
                if ($character && $character->accountId() == $user->id()) {
                    Log::debug("MultiplayerCharacter requested $characterDbref for $user - accepted as $character");
                    $user->setCharacter($character);
                    Log::withContext(['character' => $character->name]);
                } else {
                    Log::debug("MultiplayerCharacter requested $characterDbref for $user - rejected, clearing cookie.");
                    Cookie::queue(Cookie::forget('character-dbref'));
                }
            }
        }

        return $next($request);
    }
}
