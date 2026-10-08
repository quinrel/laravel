<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Schema;

class ImageSchemaEnricher
{
    /**
     * Enriches image schema with dimensions and metadata.
     *
     * @return array<string, mixed>
     */
    public function enrich(string $imageUrl): array
    {
        $schema = [
            '@type' => 'ImageObject',
            'url' => $imageUrl,
        ];

        $size = @getimagesize($imageUrl);
        if ($size) {
            $schema['width'] = $size[0];
            $schema['height'] = $size[1];
        }

        return $schema;
    }
}
