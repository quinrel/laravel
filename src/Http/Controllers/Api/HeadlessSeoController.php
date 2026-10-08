<?php

declare(strict_types=1);

namespace Holvek\Laravel\Http\Controllers\Api;

use Holvek\Laravel\Models\HolvekMeta;
use Illuminate\Http\JsonResponse;

class HeadlessSeoController
{
    public function show(int $id): JsonResponse
    {
        $meta = HolvekMeta::where('model_id', $id)->first();

        if (! $meta) {
            return response()->json(['error' => 'SEO metadata not found'], 404);
        }

        return response()->json([
            'title' => $meta->title,
            'description' => $meta->description,
            'canonical_url' => $meta->canonical_url,
            'robots' => $meta->robots,
            'og_image' => $meta->og_image,
            'social_overrides' => $meta->social_overrides,
            'schema' => [], // Schema builder output would go here
        ]);
    }

    public function global(): JsonResponse
    {
        return response()->json([
            'site_name' => config('app.name'),
            'default_image' => config('holvek.defaults.og_image'),
            'twitter_handle' => config('holvek.social.twitter'),
        ]);
    }
}
