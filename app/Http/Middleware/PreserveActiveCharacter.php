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
 * Class PreserveActiveCharacter
 * Handles the loading and saving of a character between sessions.
 */
class PreserveActiveCharacter
{
    protected MuckObjectService $muckObjectService;

    /**
     * Requests in this list won't attempt to load or save a character.
     * This is intended to avoid the work if the request is just for a resource, such as an image
     * @var array
     */
    private array $routesExempt = [
        'avatar.gradient.image',
        'avatar.gradient.previewimage',
        'multiplayer.avatar.render',
        'multiplayer.avatar.item',
        'multiplayer.avatar.itempreview'
    ];

    public function __construct(MuckObjectService $muckObjectService)
    {
        $this->muckObjectService = $muckObjectService;
    }

    public function loadCharacter(Request $request): void
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
    }

    public function saveCharacter(Request $request, mixed $response): mixed
    {
        /** @var User $user */
        $user = $request->user();
        // Some responses, e.g. binary ones, don't have a withCookie
        if ($user && ($characterDbref = $user->getCharacter()?->dbref) && method_exists($response, 'withCookie')) {
            Log::debug("Saving cookie for active character on User {$user->id()} to: $characterDbref");
            return $response->withCookie(cookie()->forever('character-dbref', $characterDbref));
        } else {
            return $response;
        }
    }

    /**
     * If a character dbref is specified, verifies and sets active character on the User object
     * Takes it from the header or cookie with the former getting precedence.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        //Allow bypass for pages that don't need a character
        if (in_array($request->route()?->getName(), $this->routesExempt)) return $next($request);

        $this->loadCharacter($request);
        $response = $next($request);
        return $this->saveCharacter($request, $response);
    }
}
