<?php

namespace App\Http\Middleware;

use Closure;
use App\User as User;
use Illuminate\Http\Request;

class HasApprovedCharacterSet
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var User $user */
        $user = $request->user();
        if (!$user) abort(500, "User should have been set before this call");

        $character = $user->getCharacter();
        if (!$character || !$character->approved()) {
            if (!$request->expectsJson()) {
                return view('multiplayer.character.select');
            }
            abort(400, "An active character either isn't set or they have not completed character generation.");
        }

        return $next($request);
    }

}
