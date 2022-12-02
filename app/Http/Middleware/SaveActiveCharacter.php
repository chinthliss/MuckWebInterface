<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


/**
 * Handles the saving of a character in a response.
 * These are different middleware because of ordering issues, if in the same file the cookie payload doesn't encrypt.
 */
class SaveActiveCharacter
{
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
        $response = $next($request);

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
}
