<?php

namespace App\Providers;

use App\Contracts\EmployeeSyncProviderInterface;
use App\Contracts\FinancialPostingProviderInterface;
use App\Models\PayrollCycle;
use App\Models\ReimbursementClaim;
use App\Services\Integrations\AiimsHttpProvider;
use App\Services\Integrations\AiimsMockProvider;
use App\Services\Integrations\FinancialErpHttpProvider;
use App\Services\Integrations\FinancialErpMockProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmployeeSyncProviderInterface::class, function () {
            return match (config('services.aiims.driver', 'mock')) {
                'http' => $this->app->make(AiimsHttpProvider::class),
                default => $this->app->make(AiimsMockProvider::class),
            };
        });

        $this->app->bind(FinancialPostingProviderInterface::class, function () {
            return match (config('services.financial_erp.driver', 'mock')) {
                'http' => $this->app->make(FinancialErpHttpProvider::class),
                default => $this->app->make(FinancialErpMockProvider::class),
            };
        });
    }

    public function boot(): void
    {
        // Real sidebar badge counts, not fabricated ones - only fires when the
        // nav is actually rendered (i.e. for an authenticated request), since
        // it's @include'd inside an @auth block in the layout.
        View::composer('layouts.partials.nav-links', function ($view) {
            $view->with([
                'pendingPayrollCount' => PayrollCycle::whereIn('status', ['hr_review', 'finance_review'])->count(),
                'pendingReimbursementCount' => ReimbursementClaim::where('status', 'pending')->count(),
            ]);
        });
    }
}
