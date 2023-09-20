<?php

namespace App\Http\Middleware;

use Closure;
use App\User as User;
use Illuminate\Http\Request;

// Redirects an account to the locked page if it's locked.
class RedirectIfLocked
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var User $user */
        $user = $request->user();
        if (!$user) abort(500, "User should have been set before this call");

        if ($user->getLockedAt()) {
            if ($request->expectsJson()) abort(403, 'Your account is locked.');
            return redirect()->route('auth.locked');
        }

        return $next($request);
    }

}
