<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\Media\AiAltTextGenerator;

class GenerateImageAltTextCommand extends Command
{
    protected $signature = 'quinrel:autofix-alt-text
                            {--model= : Model class or short name (e.g. Post or App\\Models\\Post)}
                            {--limit=100 : Maximum number of records to process}';

    protected $description = 'Automatically generate descriptive WCAG-compliant image alt text using AI';

    public function handle(AiAltTextGenerator $generator): int
    {
        $modelOption = $this->option('model');
        $limit = max(1, (int) $this->option('limit'));
        $fixedTotal = 0;

        if (is_string($modelOption) && trim($modelOption) !== '') {
            $resolvedClass = $this->resolveModelClass(trim($modelOption));

            if ($resolvedClass !== null && is_subclass_of($resolvedClass, Model::class)) {
                foreach ($resolvedClass::query()->limit($limit)->get() as $model) {
                    /** @var Model $model */
                    $fixedTotal += $generator->autofixModel($model);
                }

                $this->info("Processed [{$resolvedClass}] and generated {$fixedTotal} image alt attribute(s).");

                return self::SUCCESS;
            }

            // Fallback: filter QuinrelMeta records matching the model name
            $metas = QuinrelMeta::query()
                ->where('model_type', 'like', '%'.trim($modelOption).'%')
                ->limit($limit)
                ->get();

            foreach ($metas as $meta) {
                if ($generator->autofixMetaRecord($meta) !== null) {
                    $fixedTotal++;
                }
            }

            $this->info("Processed QuinrelMeta records for [{$modelOption}] and updated {$fixedTotal} record(s).");

            return self::SUCCESS;
        }

        $metas = QuinrelMeta::query()->limit($limit)->get();
        foreach ($metas as $meta) {
            if ($generator->autofixMetaRecord($meta) !== null) {
                $fixedTotal++;
            }
        }

        $this->info("Generated AI alt text for {$fixedTotal} QuinrelMeta record(s).");

        return self::SUCCESS;
    }

    /**
     * @return class-string<Model>|null
     */
    protected function resolveModelClass(string $input): ?string
    {
        if (class_exists($input) && is_subclass_of($input, Model::class)) {
            return $input;
        }

        $candidate = 'App\\Models\\'.ltrim($input, '\\');
        if (class_exists($candidate) && is_subclass_of($candidate, Model::class)) {
            return $candidate;
        }

        return null;
    }
}
