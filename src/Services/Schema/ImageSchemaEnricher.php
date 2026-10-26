<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Schema;

use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Support\UrlValidator;

class ImageSchemaEnricher
{
    /**
     * Enriches image schema with dimensions and metadata safely without SSRF or stream wrapper risks.
     *
     * @return array<string, mixed>
     */
    public function enrich(string $imageUrl): array
    {
        $schema = [
            '@type' => 'ImageObject',
            'url' => $imageUrl,
        ];

        $size = $this->resolveDimensions($imageUrl);
        if ($size !== null) {
            $schema['width'] = $size[0];
            $schema['height'] = $size[1];
        }

        return $schema;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    protected function resolveDimensions(string $imageUrl): ?array
    {
        // 1. Check if it is a safe relative path in public_path()
        if (str_starts_with($imageUrl, '/') && ! str_starts_with($imageUrl, '//') && ! str_contains($imageUrl, '..')) {
            $localPath = public_path(ltrim($imageUrl, '/'));
            if (is_file($localPath)) {
                $size = @getimagesize($localPath);
                if (is_array($size)) {
                    return [(int) $size[0], (int) $size[1]];
                }
            }

            return null;
        }

        // 2. Validate remote HTTP(S) URL against SSRF before fetching
        if (! UrlValidator::isSafe($imageUrl)) {
            return null;
        }

        try {
            $response = UrlValidator::safeGet($imageUrl, 3);
            if ($response !== null && $response->successful()) {
                $size = @getimagesizefromstring($response->body());
                if (is_array($size)) {
                    return [(int) $size[0], (int) $size[1]];
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
