<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Analysis;

class ContentAnalyzer
{
    public function __construct(
        protected SeoChecklist $seoChecklist,
        protected ReadabilityChecker $readabilityChecker,
        protected InternalLinkSuggester $linkSuggester
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $title, string $description, string $content, string $focusKeyword): array
    {
        return [
            'seo' => $this->seoChecklist->analyze($title, $description, $content, $focusKeyword),
            'readability' => $this->readabilityChecker->analyze($content),
            'internal_links' => $this->linkSuggester->suggest($content, $focusKeyword),
        ];
    }
}
