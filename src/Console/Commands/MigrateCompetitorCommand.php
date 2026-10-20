<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Quinrel\Laravel\Services\Migration\MigrationService;

class MigrateCompetitorCommand extends Command
{
    protected $signature = 'quinrel:migrate-competitor {package? : The competitor package to migrate from}';

    protected $description = 'Migrate SEO metadata from a competitor package.';

    public function handle(MigrationService $migrator): int
    {
        /** @var string|null $package */
        $package = $this->argument('package');

        if (! $package) {
            /** @var string $package */
            $package = $this->choice(
                'Which package would you like to migrate from?',
                ['artesaos/seotools', 'ralphjsmit/laravel-seo', 'spatie/laravel-seo']
            );
        }

        $this->info("Starting migration from {$package}...");

        if ($migrator->migrateFrom($package)) {
            $this->info("Migration from {$package} completed successfully.");

            return self::SUCCESS;
        }

        $this->error("Failed to migrate from {$package}.");

        return self::FAILURE;
    }
}
