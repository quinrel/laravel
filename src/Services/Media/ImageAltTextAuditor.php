<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Media;

class ImageAltTextAuditor
{
    /**
     * @return array<int, array{src: string, line: int}>
     */
    public function audit(string $html): array
    {
        $missing = [];

        $lines = explode("\n", $html);
        foreach ($lines as $index => $line) {
            // Find all image tags in this line
            if (preg_match_all('/<img[^>]+>/i', $line, $matches)) {
                foreach ($matches[0] as $imgTag) {
                    // Check if alt attribute is missing or empty
                    if (! preg_match('/alt=[\'"]([^\'"]+)[\'"]/i', $imgTag, $altMatches) || trim($altMatches[1]) === '') {
                        // Extract src for reporting
                        preg_match('/src=[\'"]([^\'"]+)[\'"]/i', $imgTag, $srcMatches);
                        $src = $srcMatches[1] ?? 'unknown-source';

                        $missing[] = [
                            'src' => $src,
                            'line' => $index + 1,
                        ];
                    }
                }
            }
        }

        return $missing;
    }
}
