<?php

namespace App\Http\Controllers;

use App\Models\BankAdviceFile;
use App\Models\PayrollCycle;
use App\Services\BankAdviceFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BankAdviceController extends Controller
{
    public function generate(Request $request, PayrollCycle $cycle, BankAdviceFileService $service)
    {
        try {
            $service->generate($cycle, $request->user());

            return back()->with('status', 'Bank advice file generated.');
        } catch (\Throwable $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function download(BankAdviceFile $bankAdviceFile)
    {
        return Storage::disk('local')->download($bankAdviceFile->file_path, "bank-advice-cycle-{$bankAdviceFile->payroll_cycle_id}.xlsx");
    }
}
