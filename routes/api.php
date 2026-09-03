<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inbound integration endpoints
|--------------------------------------------------------------------------
|
| The primary AIIMS integration is pull-based (see App\Services\EmployeeSyncService
| and the payroll:sync-employees command). These routes exist as an optional
| push-based alternative / health check, protected by EnsureIntegrationApiKey
| (registered globally for the "api" middleware group in bootstrap/app.php).
|
*/

Route::get('/ping', fn () => response()->json(['status' => 'ok', 'time' => now()->toIso8601String()]));
