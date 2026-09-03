<?php

namespace App\Services\Integrations;

use App\Contracts\EmployeeSyncProviderInterface;
use App\Models\School;
use GuzzleHttp\Client;
use RuntimeException;

/**
 * Real-integration stub for the AIIMS Central ERP. Structurally complete
 * (auth header, endpoint shape, JSON decoding) but the exact endpoint paths
 * and payload field names are placeholders — BRD §5/§6.1 note these are to
 * be finalized jointly with the AIIMS team. Activate by setting
 * AIIMS_DRIVER=http and the AIIMS_BASE_URL / AIIMS_API_KEY env vars.
 */
class AiimsHttpProvider implements EmployeeSyncProviderInterface
{
    private Client $client;

    public function __construct()
    {
        $baseUrl = config('services.aiims.base_url');

        if (! $baseUrl) {
            throw new RuntimeException(
                'AIIMS_DRIVER=http is set but AIIMS_BASE_URL is not configured. '.
                'Set AIIMS_DRIVER=mock until the AIIMS API contract is confirmed.'
            );
        }

        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/').'/',
            'timeout' => config('services.aiims.timeout', 30),
            'headers' => [
                'Authorization' => 'Bearer '.config('services.aiims.api_key'),
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function fetchFull(School $school): array
    {
        return $this->fetch('employees', [
            'school_code' => $school->aiims_school_code ?? $school->code,
            'mode' => 'full',
        ]);
    }

    public function fetchIncremental(School $school, \DateTimeInterface $since): array
    {
        return $this->fetch('employees', [
            'school_code' => $school->aiims_school_code ?? $school->code,
            'mode' => 'incremental',
            'since' => $since->format(\DateTimeInterface::ATOM),
        ]);
    }

    private function fetch(string $endpoint, array $query): array
    {
        $response = $this->client->get($endpoint, ['query' => $query]);

        $payload = json_decode((string) $response->getBody(), true);

        return $payload['data'] ?? [];
    }
}
