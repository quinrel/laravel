<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Services\AI\AiWritingAssistant;
use Quinrel\Laravel\Tests\TestCase;

class AiWritingAssistantTest extends TestCase
{
    public function test_it_uses_openai_when_configured(): void
    {
        config(['quinrel.ai.provider' => 'openai', 'quinrel.ai.openai_key' => 'fake-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode(['title' => 'Test Title', 'description' => 'Test Desc']),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $assistant = new AiWritingAssistant;
        $result = $assistant->suggestMetadata('Test content', 'test');

        $this->assertEquals('Test Title', $result['title']);
        $this->assertEquals('Test Desc', $result['description']);
    }
}
