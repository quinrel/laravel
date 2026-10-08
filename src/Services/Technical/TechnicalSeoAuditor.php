<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Technical;

use Illuminate\Support\Facades\Http;

class TechnicalSeoAuditor
{
    /**
     * @return array{favicon: array{status: string, message: string}, https: array{status: string, message: string}, headers: array{status: string, message: string}, hreflang: array{status: string, message: string}}
     */
    public function auditUrl(string $url): array
    {
        try {
            $response = Http::timeout(5)->get($url);
            $html = $response->body();
            $headers = $response->headers();

            return [
                'favicon' => $this->checkFavicons($html),
                'https' => $this->checkHttps($url),
                'headers' => $this->checkSecurityHeaders($headers),
                'hreflang' => $this->checkHreflang($html),
            ];
        } catch (\Exception $e) {
            $error = ['status' => 'error', 'message' => 'Failed to reach URL: '.$e->getMessage()];

            return [
                'favicon' => $error,
                'https' => $error,
                'headers' => $error,
                'hreflang' => $error,
            ];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function checkFavicons(string $html): array
    {
        $hasFavicon = preg_match('/rel=[\'"](?:shortcut )?icon[\'"]/i', $html);
        $hasAppleTouch = preg_match('/rel=[\'"]apple-touch-icon[\'"]/i', $html);

        if (! $hasFavicon) {
            return ['status' => 'error', 'message' => 'Missing standard favicon.'];
        }
        if (! $hasAppleTouch) {
            return ['status' => 'warning', 'message' => 'Missing apple-touch-icon for iOS devices.'];
        }

        return ['status' => 'success', 'message' => 'Favicons are configured correctly.'];
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function checkHttps(string $url): array
    {
        if (str_starts_with(strtolower($url), 'https://')) {
            return ['status' => 'success', 'message' => 'Uses HTTPS.'];
        }

        return ['status' => 'error', 'message' => 'Not using HTTPS. Security risk.'];
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     * @return array{status: string, message: string}
     */
    protected function checkSecurityHeaders(array $headers): array
    {
        // Simple heuristic: look for HSTS
        $headersLower = array_change_key_case($headers);

        if (isset($headersLower['strict-transport-security'])) {
            return ['status' => 'success', 'message' => 'HSTS header is present.'];
        }

        return ['status' => 'warning', 'message' => 'Missing Strict-Transport-Security (HSTS) header.'];
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function checkHreflang(string $html): array
    {
        if (preg_match('/<html[^>]*lang=[\'"]([^\'"]+)[\'"]/i', $html)) {
            return ['status' => 'success', 'message' => 'HTML lang attribute is set.'];
        }

        return ['status' => 'warning', 'message' => 'Missing lang attribute on HTML tag.'];
    }
}
