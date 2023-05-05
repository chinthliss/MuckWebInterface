<?php

namespace App\Http\Middleware;

use App\User as User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HasRole
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        /** @var User $user */
        $user = $request->user();

        if (!$user) {
            Log::Warn("HasRole was used on an incoming connection with no user. A previous middleware should be assured this.");
            abort(403);
        }

        if (!$user->hasRole($role)) { // hasRole deals with special exceptions
            abort(403);
        }
        return $next($request);
    }

}
