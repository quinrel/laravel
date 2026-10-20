<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\AI;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AiWritingAssistant
{
    /**
     * @return array<string, mixed>
     */
    public function suggestMetadata(string $content, string $keyword): array
    {
        $provider = config('quinrel.ai.provider', 'ollama');

        if ($provider === 'openai') {
            return $this->callOpenAI($content, $keyword);
        }

        if ($provider === 'anthropic') {
            return $this->callAnthropic($content, $keyword);
        }

        return $this->callOllama($content, $keyword);
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
