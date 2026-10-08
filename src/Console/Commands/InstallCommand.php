<?php

declare(strict_types=1);

namespace Holvek\Laravel\Console\Commands;

use Filament\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class InstallCommand extends Command
{
    protected $signature = 'holvek:install';

    protected $description = 'Install and configure the Holvek package';

    public function handle(): int
    {
        $this->components->info('Installing Holvek SEO Toolkit...');

        // 1. Publish Configuration
        $this->components->task('Publishing configuration', function () {
            $this->callSilent('vendor:publish', ['--tag' => 'holvek-config']);
        });

        // 2. Publish Migrations
        $this->components->task('Publishing migrations', function () {
            $this->callSilent('vendor:publish', ['--tag' => 'holvek-migrations']);
        });

        // 3. Detect UI Stack
        $this->detectAndConfigureUi();

        $this->components->info('Holvek installation complete! Run `php artisan migrate` to build tables.');

        return self::SUCCESS;
    }

    protected function detectAndConfigureUi(): void
    {
        $driver = 'standalone';

        if (class_exists(Filament::class)) {
            $this->components->info('Filament detected. Configuring Holvek for the TALL stack.');
            $driver = 'filament';
            $this->components->warn('Please ensure you register `HolvekPlugin` inside your Filament PanelProvider.');
        } elseif (class_exists(Inertia::class)) {
            $this->components->info('Inertia.js detected.');
            $choice = $this->choice(
                'How would you like to integrate the Holvek Dashboard?',
                ['Standalone (Telescope-style UI)', 'Headless (API only for custom UI)'],
                0
            );
            $driver = is_string($choice) && str_starts_with($choice, 'Standalone') ? 'standalone' : 'headless';
        }

        $this->updateEnvironmentFile('HOLVEK_UI_DRIVER', $driver);

        if ($driver === 'standalone') {
            $this->components->task('Publishing standalone dashboard assets', function () {
                $this->callSilent('vendor:publish', ['--tag' => 'holvek-assets', '--force' => true]);
            });
            $this->components->info('Standalone dashboard available at: /holvek');
        }
    }

    protected function updateEnvironmentFile(string $key, string $value): void
    {
        $path = app()->environmentFilePath();

        if (! File::exists($path)) {
            return;
        }

        $content = File::get($path);
        
        // preg_replace can return null on error
        $newContent = $content;

        if (str_contains($content, "{$key}=")) {
            $replaced = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            if (is_string($replaced)) {
                $newContent = $replaced;
            }
        } else {
            $newContent .= PHP_EOL."{$key}={$value}".PHP_EOL;
        }

        File::put($path, $newContent);
    }
}
