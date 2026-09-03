<?php

namespace App\Services\Integrations;

use App\Contracts\FinancialPostingProviderInterface;
use App\Models\JournalVoucher;
use GuzzleHttp\Client;
use RuntimeException;

/**
 * Real-integration stub for the in-house Financial ERP's voucher posting
 * endpoint. Structurally complete but the endpoint path and payload shape
 * are placeholders pending confirmation (BRD §6.2). Activate by setting
 * FINANCIAL_ERP_DRIVER=http and the FINANCIAL_ERP_BASE_URL / API_KEY env vars.
 */
class FinancialErpHttpProvider implements FinancialPostingProviderInterface
{
    private Client $client;

    public function __construct()
    {
        $baseUrl = config('services.financial_erp.base_url');

        if (! $baseUrl) {
            throw new RuntimeException(
                'FINANCIAL_ERP_DRIVER=http is set but FINANCIAL_ERP_BASE_URL is not configured. '.
                'Set FINANCIAL_ERP_DRIVER=mock until the Financial ERP posting endpoint is confirmed.'
            );
        }

        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/').'/',
            'timeout' => config('services.financial_erp.timeout', 30),
            'headers' => [
                'Authorization' => 'Bearer '.config('services.financial_erp.api_key'),
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function postVoucher(JournalVoucher $voucher): array
    {
        $response = $this->client->post('journal-vouchers', [
            'json' => [
                'voucher_number' => $voucher->voucher_number,
                'idempotency_key' => $voucher->idempotency_key,
                'lines' => $voucher->lines->map(fn ($line) => [
                    'gl_account_code' => $line->gl_account_code,
                    'cost_centre_code' => $line->cost_centre_code,
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                    'description' => $line->description,
                ])->all(),
            ],
        ]);

        $payload = json_decode((string) $response->getBody(), true);

        return [
            'external_reference' => $payload['reference'] ?? $payload['id'] ?? 'UNKNOWN',
            'posted_at' => $payload['posted_at'] ?? now()->toIso8601String(),
        ];
    }

    public function reverseVoucher(JournalVoucher $originalVoucher, JournalVoucher $reversalVoucher): array
    {
        $response = $this->client->post("journal-vouchers/{$originalVoucher->external_reference}/reverse", [
            'json' => [
                'reversal_voucher_number' => $reversalVoucher->voucher_number,
                'lines' => $reversalVoucher->lines->map(fn ($line) => [
                    'gl_account_code' => $line->gl_account_code,
                    'cost_centre_code' => $line->cost_centre_code,
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                    'description' => $line->description,
                ])->all(),
            ],
        ]);

        $payload = json_decode((string) $response->getBody(), true);

        return [
            'external_reference' => $payload['reference'] ?? $payload['id'] ?? 'UNKNOWN',
            'posted_at' => $payload['posted_at'] ?? now()->toIso8601String(),
        ];
    }
}
