<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\AI;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AiWritingAssistant
{
    /**
     * @return array<string, mixed>
     */
    public function suggestMetadata(string $content, string $keyword): array
    {
        $setupConfig = Cache::get('quinrel_setup_config');
        $setupProvider = is_array($setupConfig) && isset($setupConfig['aiProvider']) && is_string($setupConfig['aiProvider'])
            ? $setupConfig['aiProvider']
            : null;

        $configuredProvider = config('quinrel.ai.provider');
        $provider = (is_string($configuredProvider) && $configuredProvider !== '' && $configuredProvider !== 'ollama')
            ? $configuredProvider
            : ($setupProvider ?? (is_string($configuredProvider) && $configuredProvider !== '' ? $configuredProvider : 'ollama'));

        if ($provider === 'openai') {
            return $this->callOpenAI($content, $keyword);
        }

        if ($provider === 'anthropic') {
            return $this->callAnthropic($content, $keyword);
        }

        return $this->callOllama($content, $keyword);
    }

    /**
     * Generate descriptive, WCAG-compliant alt text for an image based on its filename and surrounding context.
     */
    public function suggestAltText(string $imageUrl, string $surroundingContext = '', string $articleTitle = ''): string
    {
        $filename = pathinfo((string) parse_url($imageUrl, PHP_URL_PATH), PATHINFO_FILENAME);
        $cleanFilename = trim((string) preg_replace('/[-_]+/', ' ', $filename));
        $contextPrompt = trim("Article: {$articleTitle}\nImage file: {$cleanFilename}\nSurrounding text: ".mb_substr(strip_tags($surroundingContext), 0, 500));
        $keyword = $articleTitle !== '' ? $articleTitle : ($cleanFilename !== '' ? $cleanFilename : 'image');

        $result = $this->suggestMetadata($contextPrompt, $keyword);

        foreach (['alt_text', 'alt', 'description', 'title'] as $key) {
            if (isset($result[$key]) && is_string($result[$key]) && trim($result[$key]) !== '') {
                return mb_substr(trim($result[$key]), 0, 125);
            }
        }

        if ($cleanFilename !== '' && ! preg_match('/^(?:img|dsc|dcim|image|photo|screenshot)\s*\d*$/i', $cleanFilename)) {
            return ucfirst($cleanFilename).($articleTitle !== '' ? " — {$articleTitle}" : '');
        }

        return $articleTitle !== '' ? "Illustration for {$articleTitle}" : 'Descriptive article image';
    }

    /**
     * @return array<string, mixed>
     */
    protected function callOllama(string $content, string $keyword): array
    {
        $url = (string) config('quinrel.ai.ollama_url', 'http://localhost:11434/api/generate');
        $model = (string) config('quinrel.ai.ollama_model', 'llama3');

        $prompt = "Generate an SEO title (max 60 chars) and meta description (max 155 chars) for the following content, focusing on the keyword '{$keyword}'. Return JSON with 'title' and 'description' keys only.\n\nContent:\n".mb_substr($content, 0, 2000);

        /** @var Response $response */
        $response = Http::timeout(60)->post($url, [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
            'format' => 'json',
        ]);

        if ($response->failed()) {
            return [];
        }

        $json = json_decode((string) $response->json('response'), true);

        return is_array($json) ? $json : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function callOpenAI(string $content, string $keyword): array
    {
        $key = config('quinrel.ai.openai_key');
        if (! is_string($key) || $key === '') {
            return [];
        }

        /** @var Response $response */
        $response = Http::withToken($key)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert SEO assistant. Always respond in pure JSON with keys: title, description.'],
                ['role' => 'user', 'content' => "Keyword: {$keyword}\n\nContent: ".mb_substr($content, 0, 2000)],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            return [];
        }

        $json = json_decode((string) $response->json('choices.0.message.content'), true);

        return is_array($json) ? $json : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function callAnthropic(string $content, string $keyword): array
    {
        $key = config('quinrel.ai.anthropic_key');
        if (! is_string($key) || $key === '') {
            return [];
        }

        /** @var Response $response */
        $response = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-3-5-sonnet-latest',
            'max_tokens' => 300,
            'system' => 'You are an expert SEO assistant. Always respond in pure JSON with keys: title, description.',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Generate an SEO title (max 60 chars) and meta description (max 155 chars) for the following content, focusing on the keyword '{$keyword}'. Return JSON with 'title' and 'description' keys only.\n\nContent:\n".mb_substr($content, 0, 2000),
                ],
            ],
        ]);

        if ($response->failed()) {
            return [];
        }

        $text = (string) $response->json('content.0.text');
        $json = json_decode($text, true);

        return is_array($json) ? $json : [];
    }
}
