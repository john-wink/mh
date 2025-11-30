<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TenantResolver
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tenant resolution if already resolved (e.g., by admin switching)
        if ($this->tenantManager->isResolved()) {
            return $next($request);
        }

        // Check if tenant is set in session (for admin switching)
        if ($request->hasSession() && $request->session()->has('switched_tenant_id')) {
            $tenantId = $request->session()->get('switched_tenant_id');
            $this->tenantManager->switchTenant($tenantId);

            return $next($request);
        }

        // Resolve tenant from domain
        $domain = $request->getHost();
        $tenant = $this->tenantManager->resolveTenantFromDomain($domain);

        // Set the current tenant (can be null if no tenant found)
        $this->tenantManager->setCurrentTenant($tenant);

        // If no tenant found and we're not on localhost, abort
        abort_if(! $tenant && ! in_array($domain, ['localhost', '127.0.0.1', '::1']), 404, 'Tenant not found');

        return $next($request);
    }
}
