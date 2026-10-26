<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Accessibility;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Quinrel\Laravel\Support\UrlValidator;
use Throwable;

class AccessibilityAuditor
{
    /**
     * Fetch a live URL (with SSRF validation) and run a WCAG 2.1/2.2 AA static accessibility audit.
     *
     * @return array{
     *     url: ?string,
     *     score: int,
     *     counts: array{Critical: int, Serious: int, Moderate: int},
     *     violations: array<int, array{
     *         rule_id: string,
     *         wcag: string,
     *         severity: string,
     *         line: int,
     *         message: string,
     *         snippet: string,
     *         tag: string,
     *         attributes: array<string, string>
     *     }>
     * }
     */
    public function auditUrl(string $url): array
    {
        if (! UrlValidator::isSafe($url)) {
            return [
                'url' => $url,
                'score' => 0,
                'counts' => ['Critical' => 1, 'Serious' => 0, 'Moderate' => 0],
                'violations' => [
                    [
                        'rule_id' => 'url-safety',
                        'wcag' => 'N/A',
                        'severity' => 'Critical',
                        'line' => 1,
                        'message' => 'Unsafe or restricted URL blocked by SSRF policy.',
                        'snippet' => $url,
                        'tag' => 'url',
                        'attributes' => [],
                    ],
                ],
            ];
        }

        try {
            $response = UrlValidator::safeGet($url, 10);
            if ($response === null) {
                return [
                    'url' => $url,
                    'score' => 0,
                    'counts' => ['Critical' => 1, 'Serious' => 0, 'Moderate' => 0],
                    'violations' => [
                        [
                            'rule_id' => 'url-safety',
                            'wcag' => 'N/A',
                            'severity' => 'Critical',
                            'line' => 1,
                            'message' => 'Unsafe redirect target blocked by SSRF policy.',
                            'snippet' => $url,
                            'tag' => 'url',
                            'attributes' => [],
                        ],
                    ],
                ];
            }

            if ($response->failed()) {
                return [
                    'url' => $url,
                    'score' => 0,
                    'counts' => ['Critical' => 1, 'Serious' => 0, 'Moderate' => 0],
                    'violations' => [
                        [
                            'rule_id' => 'http-fetch',
                            'wcag' => 'N/A',
                            'severity' => 'Critical',
                            'line' => 1,
                            'message' => 'Failed to fetch URL (HTTP '.$response->status().').',
                            'snippet' => $url,
                            'tag' => 'http',
                            'attributes' => [],
                        ],
                    ],
                ];
            }

            $result = $this->auditHtml($response->body());
            $result['url'] = $url;

            return $result;
        } catch (Throwable $e) {
            return [
                'url' => $url,
                'score' => 0,
                'counts' => ['Critical' => 1, 'Serious' => 0, 'Moderate' => 0],
                'violations' => [
                    [
                        'rule_id' => 'http-fetch',
                        'wcag' => 'N/A',
                        'severity' => 'Critical',
                        'line' => 1,
                        'message' => 'Failed to reach URL: '.$e->getMessage(),
                        'snippet' => $url,
                        'tag' => 'http',
                        'attributes' => [],
                    ],
                ],
            ];
        }
    }

    /**
     * Audit an HTML document or snippet against core WCAG 2.1/2.2 AA static rules.
     *
     * @return array{
     *     url: ?string,
     *     score: int,
     *     counts: array{Critical: int, Serious: int, Moderate: int},
     *     violations: array<int, array{
     *         rule_id: string,
     *         wcag: string,
     *         severity: string,
     *         line: int,
     *         message: string,
     *         snippet: string,
     *         tag: string,
     *         attributes: array<string, string>
     *     }>
     * }
     */
    public function auditHtml(string $html): array
    {
        if (trim($html) === '') {
            return [
                'url' => null,
                'score' => 100,
                'counts' => ['Critical' => 0, 'Serious' => 0, 'Moderate' => 0],
                'violations' => [],
            ];
        }

        $hasHtmlTag = (bool) preg_match('/<html\b/i', $html);
        $violations = [];

        // 1. Document Language check (<html lang="...">)
        if ($hasHtmlTag) {
            $this->checkDocumentLanguage($html, $violations);
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previousLibxml = libxml_use_internal_errors(true);

        $wrappedHtml = $hasHtmlTag
            ? '<?xml encoding="UTF-8">'.$html
            : '<?xml encoding="UTF-8"><!DOCTYPE html><html lang="en"><body>'.$html.'</body></html>';

        $dom->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxml);

        $xpath = new DOMXPath($dom);

        // 2. Image Alt Text checks
        $this->checkImages($xpath, $violations);

        // 3. Form Labeling checks
        $this->checkFormControls($xpath, $violations);

        // 4. Heading Hierarchy checks
        $this->checkHeadingHierarchy($xpath, $hasHtmlTag, $violations);

        // 5. Interactive Elements Accessible Name checks (<button>, <a>)
        $this->checkInteractiveElements($xpath, $violations);

        // 6. Duplicate Element IDs
        $this->checkDuplicateIds($xpath, $violations);

        $counts = ['Critical' => 0, 'Serious' => 0, 'Moderate' => 0];
        foreach ($violations as $violation) {
            $sev = $violation['severity'];
            if (isset($counts[$sev])) {
                $counts[$sev]++;
            }
        }

        $deductions = ($counts['Critical'] * 15) + ($counts['Serious'] * 8) + ($counts['Moderate'] * 4);
        $score = max(0, min(100, 100 - $deductions));

        return [
            'url' => null,
            'score' => $score,
            'counts' => $counts,
            'violations' => $violations,
        ];
    }

    /**
     * @param  array<int, array{rule_id: string, wcag: string, severity: string, line: int, message: string, snippet: string, tag: string, attributes: array<string, string>}>  $violations
     */
    protected function checkDocumentLanguage(string $html, array &$violations): void
    {
        if (! preg_match('/<html\b([^>]*)>/i', $html, $htmlMatch)) {
            return;
        }

        $attrs = $htmlMatch[1];
        $tagSnippet = '<html'.rtrim($attrs).'>';

        if (! preg_match('/\blang=[\'"]([^\'"]*)[\'"]/i', $attrs, $langMatch)) {
            $violations[] = [
                'rule_id' => 'html-has-lang',
                'wcag' => 'WCAG 3.1.1',
                'severity' => 'Serious',
                'line' => 1,
                'message' => 'The <html> element does not have a [lang] attribute.',
                'snippet' => $tagSnippet,
                'tag' => 'html',
                'attributes' => [],
            ];

            return;
        }

        $lang = trim($langMatch[1]);
        if ($lang === '' || ! preg_match('/^[a-zA-Z]{2,3}(?:-[a-zA-Z0-9]{2,8})*$/', $lang)) {
            $violations[] = [
                'rule_id' => 'html-valid-lang',
                'wcag' => 'WCAG 3.1.1 / 3.1.2',
                'severity' => 'Serious',
                'line' => 1,
                'message' => "The <html> element has an invalid or empty [lang=\"{$lang}\"] attribute.",
                'snippet' => $tagSnippet,
                'tag' => 'html',
                'attributes' => ['lang' => $lang],
            ];
        }
    }

    /**
     * @param  array<int, array{rule_id: string, wcag: string, severity: string, line: int, message: string, snippet: string, tag: string, attributes: array<string, string>}>  $violations
     */
    protected function checkImages(DOMXPath $xpath, array &$violations): void
    {
        $images = $xpath->query('//img');
        if ($images === false) {
            return;
        }

        foreach ($images as $img) {
            if (! $img instanceof DOMElement) {
                continue;
            }

            $role = strtolower(trim($img->getAttribute('role')));
            $ariaHidden = strtolower(trim($img->getAttribute('aria-hidden')));
            $isDecorative = in_array($role, ['presentation', 'none'], true) || $ariaHidden === 'true';

            if ($isDecorative) {
                continue;
            }

            $hasAlt = $img->hasAttribute('alt');
            $altValue = trim($img->getAttribute('alt'));
            $src = $img->getAttribute('src');

            if (! $hasAlt) {
                $violations[] = [
                    'rule_id' => 'img-alt-missing',
                    'wcag' => 'WCAG 1.1.1',
                    'severity' => 'Critical',
                    'line' => max(1, $img->getLineNo()),
                    'message' => 'Image element is missing an [alt] attribute.',
                    'snippet' => $this->renderOpeningTag($img),
                    'tag' => 'img',
                    'attributes' => $this->extractAttributes($img),
                ];

                continue;
            }

            if ($altValue === '') {
                $violations[] = [
                    'rule_id' => 'img-alt-empty',
                    'wcag' => 'WCAG 1.1.1',
                    'severity' => 'Critical',
                    'line' => max(1, $img->getLineNo()),
                    'message' => 'Non-decorative image has an empty [alt=""] attribute without role="presentation" or aria-hidden="true".',
                    'snippet' => $this->renderOpeningTag($img),
                    'tag' => 'img',
                    'attributes' => $this->extractAttributes($img),
                ];

                continue;
            }

            if ($this->isPlaceholderAltText($altValue, $src)) {
                $violations[] = [
                    'rule_id' => 'img-alt-placeholder',
                    'wcag' => 'WCAG 1.1.1',
                    'severity' => 'Serious',
                    'line' => max(1, $img->getLineNo()),
                    'message' => "Image [alt=\"{$altValue}\"] uses a non-descriptive filename or placeholder value.",
                    'snippet' => $this->renderOpeningTag($img),
                    'tag' => 'img',
                    'attributes' => $this->extractAttributes($img),
                ];
            }
        }
    }

    protected function isPlaceholderAltText(string $alt, string $src): bool
    {
        $normalized = strtolower(trim($alt));
        if (preg_match('/^(?:img[-_]?\d+|dsc[-_]?\d+|dcim[-_]?\d+|image|photo|picture|graphic|icon|untitled|placeholder|screenshot(?:\s*\d*)?)$/i', $normalized)) {
            return true;
        }

        if (preg_match('/^[^\s]+\.(?:png|jpe?g|gif|webp|svg|avif|bmp|ico)$/i', $normalized)) {
            return true;
        }

        if ($src !== '') {
            $basename = strtolower(pathinfo((string) parse_url($src, PHP_URL_PATH), PATHINFO_BASENAME));
            if ($basename !== '' && $normalized === $basename) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{rule_id: string, wcag: string, severity: string, line: int, message: string, snippet: string, tag: string, attributes: array<string, string>}>  $violations
     */
    protected function checkFormControls(DOMXPath $xpath, array &$violations): void
    {
        $controls = $xpath->query('//input | //select | //textarea');
        if ($controls === false) {
            return;
        }

        $exemptInputTypes = ['hidden', 'submit', 'reset', 'button', 'image'];

        foreach ($controls as $control) {
            if (! $control instanceof DOMElement) {
                continue;
            }

            $tagName = strtolower($control->tagName);
            if ($tagName === 'input') {
                $type = strtolower(trim($control->getAttribute('type') ?: 'text'));
                if (in_array($type, $exemptInputTypes, true)) {
                    continue;
                }
            }

            if ($this->hasAccessibleFormLabel($control, $xpath)) {
                continue;
            }

            $violations[] = [
                'rule_id' => 'form-label',
                'wcag' => 'WCAG 1.3.1 / 4.1.2',
                'severity' => 'Critical',
                'line' => max(1, $control->getLineNo()),
                'message' => "Form <{$tagName}> element lacks an associated <label for=\"...\">, wrapping <label>, [aria-label], or [aria-labelledby].",
                'snippet' => $this->renderOpeningTag($control),
                'tag' => $tagName,
                'attributes' => $this->extractAttributes($control),
            ];
        }
    }

    protected function hasAccessibleFormLabel(DOMElement $control, DOMXPath $xpath): bool
    {
        if (trim($control->getAttribute('aria-label')) !== '') {
            return true;
        }

        $labelledBy = trim($control->getAttribute('aria-labelledby'));
        if ($labelledBy !== '') {
            foreach (preg_split('/\s+/', $labelledBy) ?: [] as $refId) {
                $target = $xpath->query("//*[@id='".addslashes($refId)."']");
                $firstTarget = $target !== false ? $target->item(0) : null;
                if ($firstTarget instanceof DOMNode && trim($firstTarget->textContent) !== '') {
                    return true;
                }
            }
        }

        $id = trim($control->getAttribute('id'));
        if ($id !== '') {
            $labels = $xpath->query("//label[@for='".addslashes($id)."']");
            $firstLabel = $labels !== false ? $labels->item(0) : null;
            if ($firstLabel instanceof DOMNode && trim($firstLabel->textContent) !== '') {
                return true;
            }
        }

        // Check wrapping <label> ancestor
        $parent = $control->parentNode;
        while ($parent instanceof DOMNode) {
            if ($parent instanceof DOMElement && strtolower($parent->tagName) === 'label') {
                if (trim($parent->textContent) !== '') {
                    return true;
                }
            }
            $parent = $parent->parentNode;
        }

        return false;
    }

    /**
     * @param  array<int, array{rule_id: string, wcag: string, severity: string, line: int, message: string, snippet: string, tag: string, attributes: array<string, string>}>  $violations
     */
    protected function checkHeadingHierarchy(DOMXPath $xpath, bool $isFullDocument, array &$violations): void
    {
        $headings = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');
        if ($headings === false) {
            return;
        }

        $h1Count = 0;
        $previousLevel = 0;

        foreach ($headings as $heading) {
            if (! $heading instanceof DOMElement) {
                continue;
            }

            $level = (int) substr(strtolower($heading->tagName), 1);
            if ($level === 1) {
                $h1Count++;
                if ($h1Count > 1) {
                    $violations[] = [
                        'rule_id' => 'heading-multiple-h1',
                        'wcag' => 'WCAG 1.3.1 / 2.4.6',
                        'severity' => 'Moderate',
                        'line' => max(1, $heading->getLineNo()),
                        'message' => 'Document contains multiple <h1> elements; prefer a single primary <h1> heading.',
                        'snippet' => $this->renderElementSnippet($heading),
                        'tag' => 'h1',
                        'attributes' => $this->extractAttributes($heading),
                    ];
                }
            }

            if ($previousLevel > 0 && $level > $previousLevel + 1) {
                $violations[] = [
                    'rule_id' => 'heading-order-skipped',
                    'wcag' => 'WCAG 1.3.1 / 2.4.6',
                    'severity' => 'Moderate',
                    'line' => max(1, $heading->getLineNo()),
                    'message' => "Heading level skipped from <h{$previousLevel}> directly to <h{$level}>.",
                    'snippet' => $this->renderElementSnippet($heading),
                    'tag' => strtolower($heading->tagName),
                    'attributes' => array_merge($this->extractAttributes($heading), [
                        'expected_tag' => 'h'.($previousLevel + 1),
                    ]),
                ];
            }

            $previousLevel = $level;
        }

        if ($h1Count === 0 && ($isFullDocument || $headings->length > 0)) {
            $violations[] = [
                'rule_id' => 'heading-missing-h1',
                'wcag' => 'WCAG 1.3.1 / 2.4.6',
                'severity' => 'Serious',
                'line' => 1,
                'message' => 'Document does not contain a primary <h1> heading.',
                'snippet' => '<body>...</body>',
                'tag' => 'h1',
                'attributes' => [],
            ];
        }
    }

    /**
     * @param  array<int, array{rule_id: string, wcag: string, severity: string, line: int, message: string, snippet: string, tag: string, attributes: array<string, string>}>  $violations
     */
    protected function checkInteractiveElements(DOMXPath $xpath, array &$violations): void
    {
        $elements = $xpath->query('//button | //a[@href]');
        if ($elements === false) {
            return;
        }

        foreach ($elements as $el) {
            if (! $el instanceof DOMElement) {
                continue;
            }

            if ($this->hasAccessibleName($el, $xpath)) {
                continue;
            }

            $tag = strtolower($el->tagName);
            $violations[] = [
                'rule_id' => 'interactive-accessible-name',
                'wcag' => 'WCAG 4.1.2 / 2.4.4',
                'severity' => 'Critical',
                'line' => max(1, $el->getLineNo()),
                'message' => "Interactive <{$tag}> element has no discernible text or [aria-label] for screen readers.",
                'snippet' => $this->renderElementSnippet($el),
                'tag' => $tag,
                'attributes' => $this->extractAttributes($el),
            ];
        }
    }

    protected function hasAccessibleName(DOMElement $el, DOMXPath $xpath): bool
    {
        if (trim($el->getAttribute('aria-label')) !== '' || trim($el->getAttribute('title')) !== '') {
            return true;
        }

        $labelledBy = trim($el->getAttribute('aria-labelledby'));
        if ($labelledBy !== '') {
            foreach (preg_split('/\s+/', $labelledBy) ?: [] as $refId) {
                $target = $xpath->query("//*[@id='".addslashes($refId)."']");
                $firstTarget = $target !== false ? $target->item(0) : null;
                if ($firstTarget instanceof DOMNode && trim($firstTarget->textContent) !== '') {
                    return true;
                }
            }
        }

        if (trim($el->textContent) !== '') {
            return true;
        }

        // Check child <img alt="..."> or <svg><title>...</title></svg>
        $imgs = $xpath->query('.//img[@alt]', $el);
        if ($imgs !== false) {
            foreach ($imgs as $img) {
                if ($img instanceof DOMElement && trim($img->getAttribute('alt')) !== '') {
                    return true;
                }
            }
        }

        $svgTitles = $xpath->query('.//svg//title', $el);
        $firstTitle = $svgTitles !== false ? $svgTitles->item(0) : null;
        if ($firstTitle instanceof DOMNode && trim($firstTitle->textContent) !== '') {
            return true;
        }

        return false;
    }

    /**
     * @param  array<int, array{rule_id: string, wcag: string, severity: string, line: int, message: string, snippet: string, tag: string, attributes: array<string, string>}>  $violations
     */
    protected function checkDuplicateIds(DOMXPath $xpath, array &$violations): void
    {
        $nodesWithId = $xpath->query('//*[@id]');
        if ($nodesWithId === false) {
            return;
        }

        $seenIds = [];
        foreach ($nodesWithId as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $id = trim($node->getAttribute('id'));
            if ($id === '') {
                continue;
            }

            if (! isset($seenIds[$id])) {
                $seenIds[$id] = $node->getLineNo();

                continue;
            }

            $violations[] = [
                'rule_id' => 'duplicate-id',
                'wcag' => 'WCAG 4.1.1',
                'severity' => 'Serious',
                'line' => max(1, $node->getLineNo()),
                'message' => "Duplicate HTML attribute [id=\"{$id}\"] found (first defined on line {$seenIds[$id]}).",
                'snippet' => $this->renderOpeningTag($node),
                'tag' => strtolower($node->tagName),
                'attributes' => $this->extractAttributes($node),
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    protected function extractAttributes(DOMElement $el): array
    {
        $attrs = [];
        foreach ($el->attributes as $attr) {
            $attrs[$attr->nodeName] = $attr->nodeValue ?? '';
        }

        return $attrs;
    }

    protected function renderOpeningTag(DOMElement $el): string
    {
        $tag = strtolower($el->tagName);
        $parts = [$tag];
        foreach ($el->attributes as $attr) {
            $parts[] = sprintf('%s="%s"', $attr->nodeName, htmlspecialchars((string) $attr->nodeValue, ENT_QUOTES, 'UTF-8'));
        }

        return '<'.implode(' ', $parts).'>';
    }

    protected function renderElementSnippet(DOMElement $el): string
    {
        $doc = $el->ownerDocument;
        if ($doc === null) {
            return $this->renderOpeningTag($el);
        }

        $raw = trim((string) $doc->saveHTML($el));
        $normalized = preg_replace('/\s+/', ' ', $raw) ?? $raw;

        return mb_strlen($normalized) > 180 ? mb_substr($normalized, 0, 177).'...' : $normalized;
    }
}
