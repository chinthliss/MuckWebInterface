<?php

namespace App\Http\Middleware;

use App\HostLogManager;
use App\User as User;
use Closure;
use Illuminate\Http\Request;

/**
 * Saves an entry to the host table
 */
class LogHost
{

    /**
     * Handle the request
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        /** @var User $user */
        $user = $request->user();

        /** @var HostLogManager $hostLog */
        $hostLog = resolve(HostLogManager::class);
        $hostLog->logHost($request->ip(), $user);

        return $response;
    }
}
