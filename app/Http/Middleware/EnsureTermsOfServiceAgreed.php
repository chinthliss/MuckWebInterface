<?php

namespace App\Http\Middleware;

use Closure;
use App\User as User;
use Illuminate\Http\Request;

// Redirects an account to terms of service agreement if they haven't agreed to the latest version
class EnsureTermsOfServiceAgreed
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

        if (!$user->getAgreedToTermsofService()) {
            redirect()->setIntendedUrl($request->getRequestUri());
            return $request->expectsJson()
                ? abort(403, "Latest Terms of Service hasn't been agreed to.")
                : redirect()->route('auth.terms-of-service');
        }

        return $next($request);
    }

}
