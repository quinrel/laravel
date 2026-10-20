<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Media;

use Illuminate\Database\Eloquent\Model;
use Quinrel\Laravel\Models\QuinrelMeta;

class SpatieMediaLibraryHelper
{
    /**
     * Extracts an OpenGraph image URL from a Spatie Media Library collection.
     * Use this in an Observer or directly in the controller when saving a model.
     *
     * @param  Model  $model  The host application model (e.g. Post)
     * @param  string  $collectionName  The media collection name
     * @param  string  $conversionName  Optional conversion name (e.g. 'og-image')
     */
    public function extractOgImage(Model $model, string $collectionName = 'default', string $conversionName = ''): ?string
    {
        if (! method_exists($model, 'getFirstMediaUrl')) {
            return null;
        }

        $url = $model->getFirstMediaUrl($collectionName, $conversionName);

        if (empty($url)) {
            return null;
        }

        return $url;
    }

    /**
     * Automatically sync the first media item from a collection to the QuinrelMeta og_image field.
     */
    public function syncToQuinrelMeta(Model $model, string $collectionName = 'default', string $conversionName = ''): void
    {
        $url = $this->extractOgImage($model, $collectionName, $conversionName);

        if ($url && method_exists($model, 'quinrelMeta')) {
            $meta = $model->quinrelMeta()->firstOrCreate([]);
            if ($meta instanceof QuinrelMeta) {
                $meta->update(['og_image' => $url]);
            }
        }
    }
}
