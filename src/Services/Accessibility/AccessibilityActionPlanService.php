<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Accessibility;

class AccessibilityActionPlanService
{
    /**
     * Group accessibility violations by severity and generate remediation tickets with exact replacement snippets.
     *
     * @param  array{
     *     url?: ?string,
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
     * }  $auditResult
     * @return array{
     *     score: int,
     *     counts: array{Critical: int, Serious: int, Moderate: int},
     *     grouped: array{
     *         Critical: array<int, array<string, mixed>>,
     *         Serious: array<int, array<string, mixed>>,
     *         Moderate: array<int, array<string, mixed>>
     *     },
     *     tickets: array<int, array{
     *         rule_id: string,
     *         wcag: string,
     *         severity: string,
     *         line: int,
     *         message: string,
     *         failing_snippet: string,
     *         recommended_replacement: string
     *     }>
     * }
     */
    public function generate(array $auditResult): array
    {
        $grouped = [
            'Critical' => [],
            'Serious' => [],
            'Moderate' => [],
        ];
        $tickets = [];

        $severityRank = ['Critical' => 0, 'Serious' => 1, 'Moderate' => 2];
        $violations = $auditResult['violations'];

        usort(
            $violations,
            fn (array $a, array $b): int => ($severityRank[$a['severity']] ?? 9) <=> ($severityRank[$b['severity']] ?? 9)
        );

        foreach ($violations as $violation) {
            $replacement = $this->buildRecommendedReplacement($violation);
            $ticket = [
                'rule_id' => $violation['rule_id'],
                'wcag' => $violation['wcag'],
                'severity' => $violation['severity'],
                'line' => $violation['line'],
                'message' => $violation['message'],
                'failing_snippet' => $violation['snippet'],
                'recommended_replacement' => $replacement,
            ];

            $tickets[] = $ticket;
            if (isset($grouped[$violation['severity']])) {
                $grouped[$violation['severity']][] = $ticket;
            }
        }

        return [
            'score' => $auditResult['score'],
            'counts' => $auditResult['counts'],
            'grouped' => $grouped,
            'tickets' => $tickets,
        ];
    }

    /**
     * @param  array{
     *     rule_id: string,
     *     wcag: string,
     *     severity: string,
     *     line: int,
     *     message: string,
     *     snippet: string,
     *     tag: string,
     *     attributes: array<string, string>
     * }  $violation
     */
    protected function buildRecommendedReplacement(array $violation): string
    {
        $ruleId = $violation['rule_id'];
        $snippet = $violation['snippet'];
        $attrs = $violation['attributes'];
        $tag = $violation['tag'];

        return match ($ruleId) {
            'img-alt-missing' => $this->injectAttribute($snippet, 'alt', $this->humanizeFilename($attrs['src'] ?? 'Descriptive image text')),
            'img-alt-empty' => (string) preg_replace(
                '/\balt=[\'"][\'"]/i',
                'alt="'.htmlspecialchars($this->humanizeFilename($attrs['src'] ?? 'Descriptive image text'), ENT_QUOTES, 'UTF-8').'"',
                $snippet,
                1
            ),
            'img-alt-placeholder' => (string) preg_replace(
                '/\balt=[\'"][^\'"]*[\'"]/i',
                'alt="'.htmlspecialchars($this->humanizeFilename($attrs['src'] ?? 'Descriptive image text'), ENT_QUOTES, 'UTF-8').'"',
                $snippet,
                1
            ),
            'form-label' => $this->buildFormControlReplacement($snippet, $tag, $attrs),
            'interactive-accessible-name' => $this->injectAttribute(
                $snippet,
                'aria-label',
                $tag === 'a' ? $this->inferLinkLabel($attrs['href'] ?? '') : 'Toggle action'
            ),
            'html-has-lang', 'html-valid-lang' => '<html lang="en">',
            'heading-order-skipped' => $this->replaceHeadingLevel($snippet, $tag, $attrs['expected_tag'] ?? 'h2'),
            'heading-multiple-h1' => $this->replaceHeadingLevel($snippet, 'h1', 'h2'),
            'heading-missing-h1' => '<h1>Page Title</h1>',
            'duplicate-id' => $this->injectUniqueId($snippet, $attrs['id'] ?? 'element'),
            default => $snippet,
        };
    }

    protected function injectAttribute(string $snippet, string $attrName, string $attrValue): string
    {
        $escapedVal = htmlspecialchars($attrValue, ENT_QUOTES, 'UTF-8');

        return (string) preg_replace(
            '/^<([a-zA-Z0-9:-]+)/',
            sprintf('<$1 %s="%s"', $attrName, $escapedVal),
            $snippet,
            1
        );
    }

    /**
     * @param  array<string, string>  $attrs
     */
    protected function buildFormControlReplacement(string $snippet, string $tag, array $attrs): string
    {
        $id = $attrs['id'] ?? $attrs['name'] ?? $tag.'-field';
        $labelText = ucfirst(str_replace(['_', '-'], ' ', $id));
        $controlWithId = isset($attrs['id']) ? $snippet : $this->injectAttribute($snippet, 'id', $id);

        return sprintf('<label for="%s">%s</label>'."\n".$controlWithId, htmlspecialchars($id, ENT_QUOTES, 'UTF-8'), htmlspecialchars($labelText, ENT_QUOTES, 'UTF-8'));
    }

    protected function inferLinkLabel(string $href): string
    {
        $path = trim((string) parse_url($href, PHP_URL_PATH), '/');
        if ($path === '') {
            return 'Navigate to home page';
        }

        $parts = explode('/', $path);
        $segment = (string) end($parts);
        $readable = ucfirst(str_replace(['-', '_'], ' ', $segment));

        return "Navigate to {$readable}";
    }

    protected function replaceHeadingLevel(string $snippet, string $currentTag, string $targetTag): string
    {
        $replacedOpen = (string) preg_replace('/^<'.$currentTag.'\b/i', '<'.$targetTag, $snippet, 1);

        return (string) preg_replace('/<\/'.$currentTag.'>$/i', '</'.$targetTag.'>', $replacedOpen, 1);
    }

    protected function injectUniqueId(string $snippet, string $duplicateId): string
    {
        return (string) preg_replace(
            '/\bid=[\'"]'.preg_quote($duplicateId, '/').'[\'"]/i',
            'id="'.htmlspecialchars($duplicateId.'-2', ENT_QUOTES, 'UTF-8').'"',
            $snippet,
            1
        );
    }

    protected function humanizeFilename(string $src): string
    {
        $path = (string) parse_url($src, PHP_URL_PATH);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $clean = trim((string) preg_replace('/[-_]+/', ' ', $name));
        if ($clean === '' || preg_match('/^(?:img|dsc|dcim|image|photo|screenshot)\s*\d*$/i', $clean)) {
            return 'Descriptive illustration';
        }

        return ucfirst($clean);
    }
}
