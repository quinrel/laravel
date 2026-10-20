<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Quinrel\Laravel\Models\QuinrelMeta;

class MigrationService
{
    public function migrateFrom(string $package): bool
    {
        return match ($package) {
            'artesaos/seotools' => $this->migrateArtesaos(),
            'ralphjsmit/laravel-seo' => $this->migrateRalphjsmit(),
            'spatie/laravel-seo' => $this->migrateSpatie(),
            default => false,
        };
    }

    protected function migrateRalphjsmit(): bool
    {
        if (! Schema::hasTable('seo')) {
            return false;
        }

        DB::table('seo')->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                if (empty($row->model_type) || empty($row->model_id)) {
                    continue;
                }

                QuinrelMeta::updateOrCreate(
                    [
                        'model_type' => (string) $row->model_type,
                        'model_id' => (int) $row->model_id,
                    ],
                    [
                        'title' => $row->title ?? null,
                        'description' => $row->description ?? null,
                        'og_image' => $row->image ?? null,
                        'robots' => $row->robots ?? null,
                        'canonical_url' => $row->canonical_url ?? null,
                    ]
                );
            }
        });

        return true;
    }

    protected function migrateSpatie(): bool
    {
        $table = Schema::hasTable('seo_meta') ? 'seo_meta' : (Schema::hasTable('seo') ? 'seo' : null);
        if ($table === null) {
            return false;
        }

        DB::table($table)->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                $modelType = $row->model_type ?? $row->seoable_type ?? null;
                $modelId = $row->model_id ?? $row->seoable_id ?? null;

                if (empty($modelType) || empty($modelId)) {
                    continue;
                }

                QuinrelMeta::updateOrCreate(
                    [
                        'model_type' => (string) $modelType,
                        'model_id' => (int) $modelId,
                    ],
                    [
                        'title' => $row->title ?? null,
                        'description' => $row->description ?? null,
                        'og_image' => $row->og_image ?? $row->image ?? null,
                        'robots' => $row->robots ?? null,
                        'canonical_url' => $row->canonical_url ?? null,
                    ]
                );
            }
        });

        return true;
    }

    protected function migrateArtesaos(): bool
    {
        $table = Schema::hasTable('seotools_meta') ? 'seotools_meta' : (Schema::hasTable('seo_meta') ? 'seo_meta' : null);
        if ($table === null) {
            return false;
        }

        DB::table($table)->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                $modelType = $row->model_type ?? $row->metable_type ?? null;
                $modelId = $row->model_id ?? $row->metable_id ?? null;

                if (empty($modelType) || empty($modelId)) {
                    continue;
                }

                QuinrelMeta::updateOrCreate(
                    [
                        'model_type' => (string) $modelType,
                        'model_id' => (int) $modelId,
                    ],
                    [
                        'title' => $row->title ?? null,
                        'description' => $row->description ?? null,
                        'og_image' => $row->og_image ?? $row->image ?? null,
                        'robots' => $row->robots ?? null,
                        'canonical_url' => $row->canonical_url ?? null,
                    ]
                );
            }
        });

        return true;
    }
}
