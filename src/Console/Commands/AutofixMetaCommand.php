<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\AI\AiWritingAssistant;

class AutofixMetaCommand extends Command
{
    protected $signature = 'quinrel:autofix-meta
                            {--empty-only : Only generate metadata for records missing title or description}
                            {--limit=50 : Maximum number of records to process}';

    protected $description = 'Automatically draft missing SEO titles and descriptions using the configured AI assistant';

    public function handle(AiWritingAssistant $assistant): int
    {
        $emptyOnly = (bool) $this->option('empty-only');
        $limit = max(1, (int) $this->option('limit'));

        $query = QuinrelMeta::query();

        if ($emptyOnly) {
            $query->where(function ($q) {
                $q->whereNull('title')
                    ->orWhere('title', '')
                    ->orWhereNull('description')
                    ->orWhere('description', '');
            });
        }

        $records = $query->limit($limit)->get();

        if ($records->isEmpty()) {
            $this->info('No matching QuinrelMeta records require metadata generation.');

            return self::SUCCESS;
        }

        $updatedCount = 0;

        foreach ($records as $meta) {
            $content = $this->resolveRecordContent($meta);
            $keyword = (string) ($meta->target_keyword ?: $meta->title ?: config('app.name', 'Laravel'));

            $suggestion = $assistant->suggestMetadata($content, $keyword);
            if ($suggestion === []) {
                continue;
            }

            $updates = [];
            if (
                (! $emptyOnly || empty($meta->title))
                && isset($suggestion['title'])
                && is_string($suggestion['title'])
                && trim($suggestion['title']) !== ''
            ) {
                $updates['title'] = trim($suggestion['title']);
            }

            if (
                (! $emptyOnly || empty($meta->description))
                && isset($suggestion['description'])
                && is_string($suggestion['description'])
                && trim($suggestion['description']) !== ''
            ) {
                $updates['description'] = trim($suggestion['description']);
            }

            if ($updates !== []) {
                $meta->update($updates);
                $updatedCount++;
            }
        }

        $this->info("Successfully generated metadata for {$updatedCount} record(s).");

        return self::SUCCESS;
    }

    protected function resolveRecordContent(QuinrelMeta $meta): string
    {
        if ($meta->model_type && class_exists($meta->model_type)) {
            try {
                $model = $meta->model;
                if ($model !== null) {
                    foreach (['content', 'body', 'excerpt', 'summary', 'description', 'title', 'name'] as $field) {
                        $val = $model->getAttribute($field);
                        if (is_string($val) && trim($val) !== '') {
                            return trim(strip_tags($val));
                        }
                    }
                }
            } catch (\Throwable) {
                // Fallback to meta fields
            }
        }

        return (string) ($meta->description ?: $meta->title ?: $meta->canonical_url ?: 'Web page content');
    }
}
