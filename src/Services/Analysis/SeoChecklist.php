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
}
