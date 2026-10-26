<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Media;

use Illuminate\Database\Eloquent\Model;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\AI\AiWritingAssistant;

class AiAltTextGenerator
{
    public function __construct(
        protected ?AiWritingAssistant $aiAssistant = null,
    ) {
        $this->aiAssistant ??= app(AiWritingAssistant::class);
    }

    /**
     * Generate accessible alt text for a single image source and context.
     */
    public function generateForImage(string $imageUrl, string $surroundingContext = '', string $pageTitle = ''): string
    {
        return ($this->aiAssistant ?? new AiWritingAssistant)->suggestAltText(
            $imageUrl,
            $surroundingContext,
            $pageTitle
        );
    }

    /**
     * Scan an HTML fragment, generate AI alt text for any <img> lacking valid alt text, and return updated HTML.
     *
     * @return array{html: string, fixed_count: int, generated: array<string, string>}
     */
    public function autofixHtml(string $html, string $pageTitle = ''): array
    {
        if (! str_contains($html, '<img')) {
            return ['html' => $html, 'fixed_count' => 0, 'generated' => []];
        }

        $plainContext = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
        $fixedCount = 0;
        $generated = [];

        $updatedHtml = (string) preg_replace_callback('/<img\b([^>]*)>/i', function (array $matches) use ($plainContext, $pageTitle, &$fixedCount, &$generated): string {
            $attrs = $matches[1];

            // Skip explicitly decorative images
            if (preg_match('/\b(?:role=[\'"](?:presentation|none)[\'"]|aria-hidden=[\'"]true[\'"])/i', $attrs)) {
                return $matches[0];
            }

            $src = '';
            if (preg_match('/\bsrc=[\'"]([^\'"]+)[\'"]/i', $attrs, $srcMatch)) {
                $src = trim($srcMatch[1]);
            }

            $hasAltAttr = (bool) preg_match('/\balt=[\'"]([^\'"]*)[\'"]/i', $attrs, $altMatch);
            $currentAlt = $hasAltAttr ? trim($altMatch[1]) : '';

            if ($hasAltAttr && $currentAlt !== '' && ! $this->isPlaceholderAlt($currentAlt, $src)) {
                return $matches[0];
            }

            $altText = $this->generateForImage($src !== '' ? $src : 'image', $plainContext, $pageTitle);
            $escapedAlt = htmlspecialchars($altText, ENT_QUOTES, 'UTF-8');

            if ($hasAltAttr) {
                $newAttrs = (string) preg_replace('/\balt=[\'"][^\'"]*[\'"]/i', 'alt="'.$escapedAlt.'"', $attrs, 1);
            } else {
                $newAttrs = rtrim($attrs).' alt="'.$escapedAlt.'"';
            }

            $fixedCount++;
            $generated[$src !== '' ? $src : 'image_'.$fixedCount] = $altText;

            return '<img'.$newAttrs.'>';
        }, $html);

        return [
            'html' => $updatedHtml,
            'fixed_count' => $fixedCount,
            'generated' => $generated,
        ];
    }

    /**
     * Autofix missing image alt text on an Eloquent model's HTML/media fields.
     */
    public function autofixModel(Model $model): int
    {
        $title = '';
        foreach (['title', 'name', 'headline'] as $titleField) {
            $val = $model->getAttribute($titleField);
            if (is_string($val) && trim($val) !== '') {
                $title = trim($val);
                break;
            }
        }

        $totalFixed = 0;
        $dirty = false;

        foreach (['content', 'body', 'html', 'description'] as $htmlField) {
            $value = $model->getAttribute($htmlField);
            if (! is_string($value) || ! str_contains($value, '<img')) {
                continue;
            }

            $result = $this->autofixHtml($value, $title);
            if ($result['fixed_count'] > 0) {
                $model->setAttribute($htmlField, $result['html']);
                $totalFixed += $result['fixed_count'];
                $dirty = true;
            }
        }

        // Also support dedicated media models that have an explicit 'alt' or 'alt_text' column
        foreach (['alt', 'alt_text'] as $altColumn) {
            $attributes = $model->getAttributes();
            if (array_key_exists($altColumn, $attributes) && empty($attributes[$altColumn])) {
                $src = (string) ($model->getAttribute('url') ?: $model->getAttribute('path') ?: $model->getAttribute('file_name') ?: 'image');
                $generatedAlt = $this->generateForImage($src, $title, $title);
                $model->setAttribute($altColumn, $generatedAlt);
                $totalFixed++;
                $dirty = true;
            }
        }

        if ($dirty && $model->exists) {
            $model->save();
        }

        return $totalFixed;
    }

    /**
     * Autofix alt text for a QuinrelMeta record (and its associated morph model if present).
     */
    public function autofixMetaRecord(QuinrelMeta $meta): ?string
    {
        $generatedAlt = null;
        $title = (string) ($meta->title ?? '');
        $context = (string) ($meta->description ?? '');

        if ($meta->og_image) {
            $overrides = is_array($meta->social_overrides) ? $meta->social_overrides : [];
            if (empty($overrides['og_image_alt'])) {
                $generatedAlt = $this->generateForImage($meta->og_image, $context, $title);
                $overrides['og_image_alt'] = $generatedAlt;
                $meta->social_overrides = $overrides;
            }
        }

        if (is_string($meta->description) && str_contains($meta->description, '<img')) {
            $res = $this->autofixHtml($meta->description, $title);
            if ($res['fixed_count'] > 0) {
                $meta->description = $res['html'];
                $generatedAlt ??= (string) reset($res['generated']);
            }
        }

        if ($meta->isDirty()) {
            $meta->save();
        }

        if ($meta->model_type && class_exists($meta->model_type)) {
            try {
                $related = $meta->model;
                if ($related instanceof Model) {
                    $fixed = $this->autofixModel($related);
                    if ($fixed > 0 && $generatedAlt === null) {
                        $generatedAlt = 'Updated '.$fixed.' model image(s)';
                    }
                }
            } catch (\Throwable) {
                // Ignore morph resolution failure if table doesn't exist
            }
        }

        return $generatedAlt;
    }

    protected function isPlaceholderAlt(string $alt, string $src): bool
    {
        $normalized = strtolower(trim($alt));
        if (preg_match('/^(?:img[-_]?\d+|dsc[-_]?\d+|dcim[-_]?\d+|image|photo|picture|untitled|placeholder|screenshot(?:\s*\d*)?)$/i', $normalized)) {
            return true;
        }

        if (preg_match('/^[^\s]+\.(?:png|jpe?g|gif|webp|svg|avif|bmp|ico)$/i', $normalized)) {
            return true;
        }

        if ($src !== '') {
            $basename = strtolower(pathinfo((string) parse_url($src, PHP_URL_PATH), PATHINFO_BASENAME));
            if ($basename !== '' && $normalized === $basename) {
                return true;
            }
        }

        return false;
    }
}
