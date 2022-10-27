<?php

namespace App\Http\Middleware;

use Closure;
use App\User as User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

// Redirects an account to the locked page if it's locked.
class RedirectIfLocked
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

        if ($user->getLockedAt())
            return $request->expectsJson()
                ? abort(403, 'Your account is locked.')
                : redirect()->route('auth.locked');

        return $next($request);
    }

}