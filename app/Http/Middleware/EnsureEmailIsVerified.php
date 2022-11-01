<?php

namespace App\Http\Middleware;

use Closure;
use App\User as User;
use Illuminate\Http\Request;

// Redirects an account to email verification if their email isn't verified
class EnsureEmailIsVerified
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

        if (!$user->hasVerifiedEmail())
            return $request->expectsJson()
                ? abort(403, "Your email hasn't been verified.")
                : redirect()->route('auth.email.verify');

        return $next($request);
    }

}
