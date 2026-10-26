<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class UrlValidator
{
    /**
     * Validate that a URL uses HTTP(S) and does not target loopback, private,
     * link-local, or cloud metadata addresses (SSRF protection).
     */
    public static function isSafe(string $url): bool
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower(trim($parts['host'] ?? '', '[]'));
        if ($host === '') {
            return false;
        }

        $blockedHosts = [
            'localhost',
            'metadata',
            'metadata.google.internal',
            'instance-data',
        ];

        if (
            in_array($host, $blockedHosts, true) ||
            str_ends_with($host, '.localhost') ||
            str_ends_with($host, '.local') ||
            str_ends_with($host, '.internal')
        ) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::isPublicIp($host);
        }

        $resolvedIps = @gethostbynamel($host);
        if (is_array($resolvedIps) && $resolvedIps !== []) {
            foreach ($resolvedIps as $ip) {
                if (! self::isPublicIp($ip)) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function isPublicIp(string $ip): bool
    {
        $normalized = strtolower(trim($ip, '[]'));

        if (str_starts_with($normalized, '::ffff:')) {
            $mappedIpv4 = substr($normalized, 7);
            if (filter_var($mappedIpv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                return filter_var(
                    $mappedIpv4,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                ) !== false;
            }
        }

        return filter_var(
            $normalized,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * Perform an SSRF-safe HTTP GET request that validates every redirect hop against UrlValidator::isSafe().
     */
    public static function safeGet(string $url, int $timeout = 5, int $maxRedirects = 3): ?Response
    {
        $currentUrl = $url;

        for ($hop = 0; $hop <= $maxRedirects; $hop++) {
            if (! self::isSafe($currentUrl)) {
                return null;
            }

            /** @var Response $response */
            $response = Http::timeout($timeout)->withoutRedirecting()->get($currentUrl);

            if (! $response->redirect()) {
                return $response;
            }

            $location = $response->header('Location');
            if ($location === '') {
                return $response;
            }

            if (str_starts_with($location, '/')) {
                $parts = parse_url($currentUrl);
                $scheme = $parts['scheme'] ?? 'https';
                $host = $parts['host'] ?? '';
                $port = isset($parts['port']) ? ':'.$parts['port'] : '';
                $currentUrl = "{$scheme}://{$host}{$port}{$location}";
            } else {
                $currentUrl = $location;
            }
        }

        return null;
    }
}
