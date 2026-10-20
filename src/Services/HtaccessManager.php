<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services;

use Illuminate\Support\Facades\File;

class HtaccessManager
{
    protected string $path;

    public function __construct()
    {
        $this->path = public_path('.htaccess');
    }

    public function get(): ?string
    {
        if (File::exists($this->path)) {
            return File::get($this->path);
        }

        return null;
    }

    public function save(string $content): bool
    {
        return File::put($this->path, $content) !== false;
    }
}
