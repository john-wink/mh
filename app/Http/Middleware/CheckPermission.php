<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (! $request->user()) {
            abort(401, 'Unauthenticated');
        }

        if (! $request->user()->hasAnyPermission($permissions)) {
            abort(403, 'Insufficient permissions');
        }

        return $next($request);
    }
}
