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
        abort_unless($request->user(), 401, 'Unauthenticated');

        abort_unless($request->user()->hasAnyPermission($permissions), 403, 'Insufficient permissions');

        return $next($request);
    }
}
