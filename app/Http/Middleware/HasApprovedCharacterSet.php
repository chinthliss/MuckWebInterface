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

        if (!$character) {
            if (!$request->expectsJson()) {
                // Don't use the flash since the page content includes more detail.
                // session()->flash('message-success', 'You need to select an existing character or create a new one to continue.');
                redirect()->setIntendedUrl($request->getRequestUri());
                return redirect(route('multiplayer.character.required'));
            }
            abort(400, "Active character hasn't been set or specified correctly.");
        }

        if (!$character->approved()) {
            if (!$request->expectsJson()) {
                session()->flash('message-success', 'You need to complete character generation to continue.');
                redirect()->setIntendedUrl($request->getRequestUri());
                return redirect(route('multiplayer.character.initial-setup'));
            }
            abort(400, "Active character hasn't been set or specified correctly.");
        }

        return $next($request);
    }

}
