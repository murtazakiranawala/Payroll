<?php

return [

    /*
     * AIIMS Central ERP — source of employee master data (BRD FR-1).
     * driver: "mock" returns realistic fixture data so the payroll cycle can be
     * demoed end-to-end; switch to "http" once AIIMS confirms its API contract.
     */
    'aiims' => [
        'driver' => env('AIIMS_DRIVER', 'mock'),
        'base_url' => env('AIIMS_BASE_URL'),
        'api_key' => env('AIIMS_API_KEY'),
        'timeout' => (int) env('AIIMS_TIMEOUT', 30),
    ],

    /*
     * In-house Financial ERP — destination for payroll journal vouchers (BRD FR-3).
     * driver: "mock" simulates posting and returns a fake external reference;
     * switch to "http" once the Financial ERP's posting endpoint is confirmed.
     */
    'financial_erp' => [
        'driver' => env('FINANCIAL_ERP_DRIVER', 'mock'),
        'base_url' => env('FINANCIAL_ERP_BASE_URL'),
        'api_key' => env('FINANCIAL_ERP_API_KEY'),
        'timeout' => (int) env('FINANCIAL_ERP_TIMEOUT', 30),
    ],

    /*
     * Static API key(s) accepted on the inbound integration endpoints defined
     * in routes/api.php (e.g. an alternative push-based AIIMS notification hook).
     */
    'payroll_inbound_api_key' => env('PAYROLL_INBOUND_API_KEY'),

];
