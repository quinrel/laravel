<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Quinrel\Laravel\Models\QuinrelMeta;

class HeadlessSeoController
{
    public function show(Request $request, int $id): JsonResponse
    {
        $query = QuinrelMeta::where('model_id', $id);

        $modelType = $request->query('model_type');
        if (is_string($modelType) && $modelType !== '') {
            $query->where('model_type', $modelType);
        }

        $meta = $query->first();

        if (! $meta) {
            return response()->json(['error' => 'SEO metadata not found'], 404);
        }

        return response()->json([
            'title' => $meta->title,
            'description' => $meta->description,
            'canonical_url' => $meta->canonical_url,
            'robots' => $meta->robots,
            'og_image' => $meta->og_image,
            'hreflang_links' => $meta->hreflang_links ?? [],
            'social_overrides' => $meta->social_overrides ?? [],
            'schema' => $meta->custom_schema ?? [],
        ]);
    }

    public function global(): JsonResponse
    {
        return response()->json([
            'site_name' => config('app.name'),
            'default_image' => config('quinrel.defaults.og_image'),
            'twitter_handle' => config('quinrel.social.twitter'),
        ]);
    }
}
