<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Analysis;

class SeoChecklist
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(string $title, string $description, string $content, string $focusKeyword): array
    {
        $keywordLower = strtolower($focusKeyword);
        $contentLower = strtolower($content);

        return [
            'title_length' => $this->evaluateTitleLength($title),
            'description_length' => $this->evaluateDescriptionLength($description),
            'keyword_in_title' => $this->evaluateKeywordInTitle($title, $keywordLower),
            'keyword_in_description' => $this->evaluateKeywordInDescription($description, $keywordLower),
            'keyword_in_content' => $this->evaluateKeywordDensity($contentLower, $keywordLower),
            'headings_check' => $this->evaluateHeadings($content),
            'alt_text_check' => $this->evaluateAltText($content),
            'geo_direct_answers' => $this->evaluateDirectAnswerBlocks($content),
            'geo_structured_data' => $this->evaluateStructuredFormatting($content),
            'geo_factual_density' => $this->evaluateFactualDensity($content),
        ];
    }

    /**
     * Evaluate content specifically for Generative Engine Optimization (GEO) & AI Citability.
     *
     * @return array{
     *     score: int,
     *     checks: array{
     *         direct_answer_blocks: array{status: string, message: string},
     *         structured_formatting: array{status: string, message: string},
     *         factual_density: array{status: string, message: string}
     *     },
     *     recommendations: array<int, string>
     * }
     */
    public function analyzeGeo(string $content): array
    {
        $checks = [
            'direct_answer_blocks' => $this->evaluateDirectAnswerBlocks($content),
            'structured_formatting' => $this->evaluateStructuredFormatting($content),
            'factual_density' => $this->evaluateFactualDensity($content),
        ];

        $score = 0;
        $recommendations = [];

        foreach ($checks as $check) {
            if ($check['status'] === 'success') {
                $score += 34;
            } elseif ($check['status'] === 'warning') {
                $score += 17;
                $recommendations[] = $check['message'];
            } else {
                $recommendations[] = $check['message'];
            }
        }

        return [
            'score' => min(100, $score),
            'checks' => $checks,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function evaluateTitleLength(string $title): array
    {
        $length = mb_strlen($title);
        if ($length === 0) {
            return ['status' => 'error', 'message' => 'Title is missing.'];
        }
        if ($length < 30) {
            return ['status' => 'warning', 'message' => 'Title is too short (under 30 characters).'];
        }
        if ($length > 60) {
            return ['status' => 'warning', 'message' => 'Title is too long (over 60 characters).'];
        }

        return ['status' => 'success', 'message' => 'Title length is optimal.'];
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function evaluateDescriptionLength(string $description): array
    {
        $length = mb_strlen($description);
        if ($length === 0) {
            return ['status' => 'error', 'message' => 'Meta description is missing.'];
        }
        if ($length < 120) {
            return ['status' => 'warning', 'message' => 'Meta description is too short (under 120 characters).'];
        }
        if ($length > 160) {
            return ['status' => 'warning', 'message' => 'Meta description is too long (over 160 characters).'];
        }

        return ['status' => 'success', 'message' => 'Meta description length is optimal.'];
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function evaluateKeywordInTitle(string $title, string $keyword): array
    {
        if (empty($keyword)) {
            return ['status' => 'warning', 'message' => 'No focus keyword set.'];
        }
        if (str_contains(strtolower($title), $keyword)) {
            return ['status' => 'success', 'message' => 'Focus keyword found in title.'];
        }

        return ['status' => 'error', 'message' => 'Focus keyword not found in title.'];
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function evaluateKeywordInDescription(string $description, string $keyword): array
    {
        if (empty($keyword)) {
            return ['status' => 'warning', 'message' => 'No focus keyword set.'];
        }
        if (str_contains(strtolower($description), $keyword)) {
            return ['status' => 'success', 'message' => 'Focus keyword found in meta description.'];
        }

        return ['status' => 'error', 'message' => 'Focus keyword not found in meta description.'];
    }

    /**
     * @return array{status: string, message: string, density: float}
     */
    protected function evaluateKeywordDensity(string $content, string $keyword): array
    {
        if (empty($keyword)) {
            return ['status' => 'warning', 'message' => 'No focus keyword set.', 'density' => 0.0];
        }

        $wordCount = str_word_count(strip_tags($content));
        if ($wordCount === 0) {
            return ['status' => 'error', 'message' => 'Content is empty.', 'density' => 0.0];
        }

        $keywordCount = substr_count($content, $keyword);
        $density = ($keywordCount / $wordCount) * 100;

        if ($density === 0.0) {
            return ['status' => 'error', 'message' => 'Focus keyword not found in content.', 'density' => 0.0];
        }
        if ($density > 3.0) {
            return ['status' => 'warning', 'message' => 'Keyword density is high (over 3%). Risk of keyword stuffing.', 'density' => round($density, 2)];
        }

        return ['status' => 'success', 'message' => 'Keyword density is optimal.', 'density' => round($density, 2)];
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function evaluateHeadings(string $content): array
    {
        $hasH1 = preg_match('/<h1[^>]*>/i', $content) === 1;
        $hasH2 = preg_match('/<h2[^>]*>/i', $content) === 1;

        if (! $hasH1) {
            return ['status' => 'warning', 'message' => 'No H1 heading found in content.'];
        }
        if (! $hasH2) {
            return ['status' => 'warning', 'message' => 'No H2 headings found. Consider breaking up text.'];
        }

        return ['status' => 'success', 'message' => 'Good use of headings.'];
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function evaluateAltText(string $content): array
    {
        $imagesCount = preg_match_all('/<img[^>]+>/i', $content, $images);
        if ($imagesCount === 0) {
            return ['status' => 'success', 'message' => 'No images to check for alt text.'];
        }

        $missingAlt = 0;
        foreach ($images[0] as $imgTag) {
            if (! preg_match('/alt=[\'"]([^\'"]+)[\'"]/i', $imgTag)) {
                $missingAlt++;
            }
        }

        if ($missingAlt > 0) {
            return ['status' => 'warning', 'message' => "{$missingAlt} images are missing alt text."];
        }

        return ['status' => 'success', 'message' => 'All images have alt text.'];
    }

    /**
     * Evaluate whether H2/H3 question headings are followed by concise direct-answer paragraphs (20–90 words)
     * that AI answer engines can extract directly as citations.
     *
     * @return array{status: string, message: string}
     */
    protected function evaluateDirectAnswerBlocks(string $content): array
    {
        $pattern = '/<h[23][^>]*>(.*?)<\/h[23]>\s*<p[^>]*>(.*?)<\/p>/is';
        $matchCount = preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        if ($matchCount === 0 || $matchCount === false) {
            return [
                'status' => 'warning',
                'message' => 'Add H2/H3 headings followed immediately by a concise direct-answer paragraph (25–80 words) for AI answer extraction.',
            ];
        }

        $questionAnswers = 0;
        $conciseSectionAnswers = 0;

        foreach ($matches as $match) {
            $headingText = trim(strip_tags($match[1]));
            $paragraphWords = str_word_count(trim(strip_tags($match[2])));

            $isQuestion = str_ends_with($headingText, '?')
                || preg_match('/^(?:what|how|why|when|where|which|who|can|is|does|should)\b/i', $headingText) === 1;

            if ($paragraphWords >= 15 && $paragraphWords <= 90) {
                $conciseSectionAnswers++;
                if ($isQuestion) {
                    $questionAnswers++;
                }
            }
        }

        if ($questionAnswers > 0) {
            return [
                'status' => 'success',
                'message' => "Found {$questionAnswers} question-heading block(s) with concise direct answers ready for AI citation.",
            ];
        }

        if ($conciseSectionAnswers > 0) {
            return [
                'status' => 'warning',
                'message' => 'Sections have concise lead paragraphs, but phrasing key H2/H3 headings as natural questions improves AI overview matching.',
            ];
        }

        return [
            'status' => 'warning',
            'message' => 'Lead paragraphs after H2/H3 headings should be 15–90 words so LLMs can quote them as self-contained answer capsules.',
        ];
    }

    /**
     * Evaluate presence of structured HTML lists, tables, or definition lists for LLM parsing.
     *
     * @return array{status: string, message: string}
     */
    protected function evaluateStructuredFormatting(string $content): array
    {
        $hasList = preg_match('/<(?:ul|ol|dl)\b[^>]*>/i', $content) === 1;
        $hasTable = preg_match('/<table\b[^>]*>/i', $content) === 1;

        if ($hasList && $hasTable) {
            return [
                'status' => 'success',
                'message' => 'Content includes both structured lists and comparison tables for high-fidelity LLM synthesis.',
            ];
        }

        if ($hasList || $hasTable) {
            return [
                'status' => 'success',
                'message' => 'Content includes structured list or table markup for AI answer extraction.',
            ];
        }

        return [
            'status' => 'warning',
            'message' => 'No <ul>, <ol>, or <table> elements found. Structured lists and tables significantly increase AI answer engine citations.',
        ];
    }

    /**
     * Evaluate factual and statistical density (quantitative data points and outbound source citations).
     *
     * @return array{status: string, message: string}
     */
    protected function evaluateFactualDensity(string $content): array
    {
        $plain = strip_tags($content);
        $statMatches = preg_match_all('/\b\d+(?:\.\d+)?(?:\s*%|\s*(?:x|ms|s|seconds|minutes|hours|days|months|years|users|pages|queries|million|billion)\b)/i', $plain);
        $citationLinks = preg_match_all('/<a\b[^>]*href=[\'"]https?:\/\/[^\'"]+[\'"][^>]*>/i', $content);

        $statsCount = is_int($statMatches) ? $statMatches : 0;
        $linksCount = is_int($citationLinks) ? $citationLinks : 0;

        if ($statsCount >= 2 && $linksCount >= 1) {
            return [
                'status' => 'success',
                'message' => 'Strong factual density with quantitative statistics and verifiable reference links.',
            ];
        }

        if ($statsCount >= 1 || $linksCount >= 1) {
            return [
                'status' => 'warning',
                'message' => 'Moderate factual signals. Adding specific metrics (%, benchmarks) and source links increases GEO citation probability.',
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Low factual density: add concrete statistics, benchmarks, or authoritative reference links to earn LLM citations.',
        ];
    }
}
