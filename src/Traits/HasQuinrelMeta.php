<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use Quinrel\Laravel\Models\QuinrelMeta;

/**
 * @mixin Model
 *
 * @property ?QuinrelMeta $quinrelMeta
 */
trait HasQuinrelMeta
{
    /**
     * @return MorphOne<QuinrelMeta, $this>
     */
    public function quinrelMeta(): MorphOne
    {
        return $this->morphOne(QuinrelMeta::class, 'model');
    }

    public function getSeoTitle(): ?string
    {
        if (! empty($this->quinrelMeta?->title)) {
            return $this->quinrelMeta->title;
        }

        foreach (['seo_title', 'title', 'name', 'headline'] as $attribute) {
            $value = $this->getAttribute($attribute);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    public function getSeoDescription(): ?string
    {
        if (! empty($this->quinrelMeta?->description)) {
            return $this->quinrelMeta->description;
        }

        foreach (['seo_description', 'excerpt', 'summary', 'description', 'body', 'content'] as $attribute) {
            $value = $this->getAttribute($attribute);
            if (is_string($value) && trim($value) !== '') {
                $clean = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');
                if ($clean !== '') {
                    return Str::limit($clean, 155);
                }
            }
        }

        return null;
    }

    public function getSeoImage(): ?string
    {
        if (! empty($this->quinrelMeta?->og_image)) {
            return $this->quinrelMeta->og_image;
        }

        foreach (['og_image', 'featured_image', 'image', 'thumbnail', 'cover_image'] as $attribute) {
            $value = $this->getAttribute($attribute);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
