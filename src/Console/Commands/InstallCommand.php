<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Console\Commands;

use Filament\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class InstallCommand extends Command
{
    protected $signature = 'quinrel:install';

    protected $description = 'Install and configure the Quinrel package';

    public function handle(): int
    {
        $this->components->info('Installing Quinrel SEO Toolkit...');

        // 1. Publish Configuration
        $this->components->task('Publishing configuration', function () {
            $this->callSilent('vendor:publish', ['--tag' => 'quinrel-config']);
        });

        // 2. Publish Migrations
        $this->components->task('Publishing migrations', function () {
            $this->callSilent('vendor:publish', ['--tag' => 'quinrel-migrations']);
        });

        // 3. Generate IndexNow Key
        $this->components->task('Generating IndexNow key', function () {
            $this->generateIndexNowKey();
        });

        // 4. Detect UI Stack
        $this->detectAndConfigureUi();

        $this->components->info('Quinrel installation complete! Run `php artisan migrate` to build tables.');

        return self::SUCCESS;
    }

    protected function generateIndexNowKey(): void
    {
        $path = app()->environmentFilePath();
        if (! File::exists($path)) {
            return;
        }

        $content = File::get($path);
        if (str_contains($content, 'QUINREL_INDEXNOW_KEY=')) {
            return; // Already generated
        }

        $key = bin2hex(random_bytes(16)); // 32 chars
        $this->updateEnvironmentFile('QUINREL_INDEXNOW_KEY', $key);
    }

    protected function detectAndConfigureUi(): void
    {
        $driver = 'standalone';

        if (class_exists(Filament::class)) {
            $this->components->info('Filament detected. Configuring Quinrel for the TALL stack.');
            $driver = 'filament';
            $this->components->warn('Please ensure you register `QuinrelPlugin` inside your Filament PanelProvider.');
        } elseif (class_exists(Inertia::class)) {
            $this->components->info('Inertia.js detected.');
            $choice = $this->choice(
                'How would you like to integrate the Quinrel Dashboard?',
                ['Standalone (Telescope-style UI)', 'Headless (API only for custom UI)'],
                0
            );
            $driver = is_string($choice) && str_starts_with($choice, 'Standalone') ? 'standalone' : 'headless';
        }

        $this->updateEnvironmentFile('QUINREL_UI_DRIVER', $driver);

        if ($driver === 'standalone') {
            $this->components->task('Publishing standalone dashboard assets', function () {
                $this->callSilent('vendor:publish', ['--tag' => 'quinrel-assets', '--force' => true]);
            });
            $this->components->info('Standalone dashboard available at: /quinrel');
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
