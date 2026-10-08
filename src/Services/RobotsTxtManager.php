<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services;

use Illuminate\Support\Facades\File;

class RobotsTxtManager
{
    protected string $path;

    public function __construct()
    {
        $this->path = public_path('robots.txt');
    }

    public function get(): string
    {
        if (File::exists($this->path)) {
            return File::get($this->path);
        }

        return "User-agent: *\nDisallow:";
    }

    public function save(string $content): bool
    {
        return File::put($this->path, $content) !== false;
    }
}
