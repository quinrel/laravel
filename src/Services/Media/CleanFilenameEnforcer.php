<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Media;

use Illuminate\Support\Str;

class CleanFilenameEnforcer
{
    /**
     * Convert a filename to a lowercase, hyphen-separated, SEO-friendly format.
     * Preserves the extension.
     */
    public function enforce(string $filename): string
    {
        $info = pathinfo($filename);

        $name = $info['filename'];
        $extension = isset($info['extension']) ? '.'.strtolower($info['extension']) : '';

        $cleanName = Str::slug($name);

        // If the resulting slug is empty, fallback to a unique id
        if (empty($cleanName)) {
            $cleanName = 'image-'.uniqid();
        }

        return $cleanName.$extension;
    }
}
