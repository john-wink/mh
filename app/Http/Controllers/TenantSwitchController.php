<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TenantSwitchController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Switch to a different tenant (for admin users)
     */
    public function switch(Request $request, int $tenantId): RedirectResponse
    {
        // TODO: Add authorization check to ensure user can switch tenants
        // This should only be available to super admin users

        $success = $this->tenantManager->switchTenant($tenantId);

        if (! $success) {
            return redirect()->back()->with('error', 'Tenant not found or inactive');
        }

        // Store tenant switch in session
        $request->session()->put('switched_tenant_id', $tenantId);

        return redirect()->route('dashboard')->with('success', 'Switched to tenant successfully');
    }

    /**
     * Clear tenant switch and return to original tenant resolution
     */
    public function clear(Request $request): RedirectResponse
    {
        // Clear switched tenant from session
        $request->session()->forget('switched_tenant_id');

        // Clear current tenant
        $this->tenantManager->clearTenant();

        return redirect()->route('dashboard')->with('success', 'Cleared tenant switch');
    }
}
