<?php

declare(strict_types=1);

namespace Quinrel\Laravel\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Quinrel\Laravel\Services\HeadRenderer;

class Image extends Component
{
    public function __construct(
        public string $src,
        public string $alt = '',
        public ?int $width = null,
        public ?int $height = null,
        public bool $hero = false,
        public ?string $class = null,
    ) {
        if ($this->hero && $this->src !== '') {
            app(HeadRenderer::class)->addPreload($this->src, 'image');
        }
    }

    public function render(): View|Closure|string
    {
        return function (array $data): string {
            $attrs = [
                sprintf('src="%s"', e($this->src)),
                sprintf('alt="%s"', e($this->alt)),
                sprintf('loading="%s"', $this->hero ? 'eager' : 'lazy'),
                'decoding="async"',
            ];

            if ($this->hero) {
                $attrs[] = 'fetchpriority="high"';
            }

            if ($this->width !== null) {
                $attrs[] = sprintf('width="%d"', $this->width);
            }

            if ($this->height !== null) {
                $attrs[] = sprintf('height="%d"', $this->height);
            }

            if ($this->class !== null && $this->class !== '') {
                $attrs[] = sprintf('class="%s"', e($this->class));
            }

            $extraAttributes = isset($data['attributes']) ? trim((string) $data['attributes']) : '';
            if ($extraAttributes !== '') {
                $attrs[] = $extraAttributes;
            }

            return '<img '.implode(' ', $attrs).'>';
        };
    }
}
