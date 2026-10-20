<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Support;

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
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
