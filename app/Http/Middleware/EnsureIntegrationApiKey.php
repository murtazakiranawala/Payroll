<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lightweight shared-secret auth for the inbound integration endpoints
 * (routes/api.php). Real deployments should replace this with whatever
 * auth scheme AIIMS / the Financial ERP standardize on, once confirmed.
 */
class EnsureIntegrationApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.payroll_inbound_api_key');
        $provided = $request->header('X-Api-Key');

        if (! $expected || ! $provided || ! hash_equals($expected, $provided)) {
            abort(401, 'Invalid or missing API key.');
        }

        return $next($request);
    }
}
