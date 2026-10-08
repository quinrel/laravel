<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\AI;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AiWritingAssistant
{
    /**
     * @return array<string, mixed>
     */
    public function suggestMetadata(string $content, string $keyword): array
    {
        $provider = config('holvek.ai.provider', 'ollama');

        if ($provider === 'openai') {
            return $this->callOpenAI($content, $keyword);
        }

        if ($provider === 'anthropic') {
            return $this->callAnthropic($content, $keyword);
        }

        // Default to local Ollama
        return $this->callOllama($content, $keyword);
    }

    /**
     * @return array<string, mixed>
     */
    protected function callOllama(string $content, string $keyword): array
    {
        $url = config('holvek.ai.ollama_url', 'http://localhost:11434/api/generate');
        $model = config('holvek.ai.ollama_model', 'llama3');

        $prompt = "Generate an SEO title (max 60 chars) and meta description (max 155 chars) for the following content, focusing on the keyword '{$keyword}'. Return JSON with 'title' and 'description' keys only.\n\nContent:\n".substr($content, 0, 2000);

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
        $key = config('holvek.ai.openai_key');
        if (! $key) {
            return [];
        }

        /** @var Response $response */
        $response = Http::withToken((string) $key)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert SEO assistant. Always respond in pure JSON with keys: title, description.'],
                ['role' => 'user', 'content' => "Keyword: {$keyword}\n\nContent: ".substr($content, 0, 2000)],
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
        // Similar implementation for Anthropic
        return [
            'title' => 'Anthropic Suggested Title',
            'description' => 'Anthropic Suggested Description',
        ];
    }
}
