<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Analysis;

class ReadabilityChecker
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(string $content): array
    {
        $text = strip_tags($content);
        $words = str_word_count($text);
        if ($words === 0) {
            return [
                'score' => 0,
                'level' => 'N/A',
                'message' => 'Not enough text to analyze.',
            ];
        }

        $sentences = preg_match_all('/[^\.\!\?]+[\.\!\?]+/', $text);
        $sentences = max(1, $sentences);

        $syllables = $this->countSyllables($text);

        $score = 206.835 - 1.015 * ($words / $sentences) - 84.6 * ($syllables / $words);
        $score = max(0, min(100, $score));

        return [
            'score' => round($score, 1),
            'level' => $this->getReadabilityLevel($score),
            'message' => $this->getReadabilityMessage($score),
            'stats' => [
                'words' => $words,
                'sentences' => $sentences,
                'syllables' => $syllables,
            ],
        ];
    }

    protected function countSyllables(string $text): int
    {
        $words = str_word_count($text, 1);
        $count = 0;
        foreach ($words as $word) {
            $count += $this->countWordSyllables($word);
        }

        return $count;
    }

    protected function countWordSyllables(string $word): int
    {
        $word = strtolower($word);
        if (strlen($word) <= 3) {
            return 1;
        }

        $word = preg_replace('/(?:[^laeiouy]es|ed|[^laeiouy]e)$/', '', $word);
        $word = preg_replace('/^y/', '', (string) $word);

        $matches = preg_match_all('/[aeiouy]{1,2}/', (string) $word);

        return max(1, $matches ?: 1);
    }

    protected function getReadabilityLevel(float $score): string
    {
        if ($score >= 90) {
            return 'Very Easy';
        }
        if ($score >= 80) {
            return 'Easy';
        }
        if ($score >= 70) {
            return 'Fairly Easy';
        }
        if ($score >= 60) {
            return 'Standard';
        }
        if ($score >= 50) {
            return 'Fairly Difficult';
        }
        if ($score >= 30) {
            return 'Difficult';
        }

        return 'Very Confusing';
    }

    protected function getReadabilityMessage(float $score): string
    {
        if ($score >= 60) {
            return 'Readability is good and accessible to most readers.';
        }

        return 'Text may be too complex. Consider shorter sentences and simpler words.';
    }
}
