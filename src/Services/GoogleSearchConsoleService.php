<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSearchConsoleService
{
    /**
     * Get the access token for Google API.
     */
    public function getAccessToken(): string
    {
        // If a static OAuth token is provided, use it.
        $staticToken = config('holvek.api.google_oauth_token');
        if ($staticToken) {
            return $staticToken;
        }

        return Cache::remember('holvek_gsc_access_token', 3000, function () {
            return $this->fetchTokenFromServiceAccount();
        });
    }

    protected function fetchTokenFromServiceAccount(): string
    {
        $serviceAccount = config('holvek.api.gsc_service_account');

        if (! $serviceAccount) {
            throw new RuntimeException('Google Service Account or OAuth token is not configured for GSC.');
        }

        $credentials = $this->parseServiceAccount($serviceAccount);

        $jwt = $this->createJwt($credentials);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to authenticate with Google: '.$response->body());
        }

        return $response->json('access_token');
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseServiceAccount(string $configValue): array
    {
        if (is_file($configValue) && is_readable($configValue)) {
            $content = file_get_contents($configValue);
        } else {
            $content = $configValue;
        }

        $credentials = json_decode((string) $content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($credentials['private_key'], $credentials['client_email'])) {
            throw new RuntimeException('Invalid Google Service Account JSON.');
        }

        return $credentials;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    protected function createJwt(array $credentials): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
        $now = time();
        $payload = json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode((string) $header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode((string) $payload));

        $signatureInput = $base64UrlHeader.'.'.$base64UrlPayload;

        $signature = '';
        if (! openssl_sign($signatureInput, $signature, $credentials['private_key'], 'SHA256')) {
            throw new RuntimeException('Failed to sign JWT for Google Auth.');
        }

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $signatureInput.'.'.$base64UrlSignature;
    }

    /**
     * Fetch GSC analytics for a given site and date.
     *
     * @param  string  $date  (YYYY-MM-DD)
     * @param  array<int, string>  $dimensions  (e.g., ['page'])
     * @return array<int, array<string, mixed>>
     */
    public function queryAnalytics(string $siteUrl, string $date, array $dimensions = []): array
    {
        $token = $this->getAccessToken();

        $payload = [
            'startDate' => $date,
            'endDate' => $date,
            'dimensions' => $dimensions,
            'rowLimit' => 5000,
        ];

        $encodedSiteUrl = urlencode($siteUrl);

        $response = Http::withToken($token)
            ->post("https://searchconsole.googleapis.com/webmasters/v3/sites/{$encodedSiteUrl}/searchAnalytics/query", $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch GSC analytics: '.$response->body());
        }

        return $response->json('rows') ?? [];
    }
}
