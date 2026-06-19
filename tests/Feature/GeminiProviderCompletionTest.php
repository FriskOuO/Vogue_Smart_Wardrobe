<?php

namespace Tests\Feature;

use App\Services\GeminiChatAssistantService;
use App\Services\GeminiTextUnderstandingService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiProviderCompletionTest extends TestCase
{
    public function test_gemini_chat_assistant_degrades_safely_when_key_is_missing(): void
    {
        Config::set('ai.gemini_api_key', null);
        Config::set('ai.chat_model', 'gemini-2.5-flash');

        $result = app(GeminiChatAssistantService::class)->reply([
            'mock_mode' => false,
            'message' => '今天要穿什麼？',
        ]);

        $this->assertSame('degraded', $result['chat_generation']['status']);
        $this->assertSame('GEMINI_API_KEY_MISSING', $result['chat_generation']['error_code']);
        $this->assertTrue($result['chat_generation']['fallback_active']);
    }

    public function test_gemini_chat_assistant_can_record_ready_response(): void
    {
        Config::set('ai.gemini_api_key', 'test-gemini-key');
        Config::set('ai.chat_model', 'gemini-2.5-flash');
        Config::set('ai.gemini_api_base_url', 'https://generativelanguage.googleapis.com');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'reply' => '今天可以用白襯衫搭配深色外套。',
                                        'suggested_actions' => ['查看白色上衣', '保存穿搭'],
                                        'safety_notes' => ['只使用衣櫥中存在的單品'],
                                        'context_summary' => '2 closet items used',
                                    ], JSON_UNESCAPED_UNICODE),
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $result = app(GeminiChatAssistantService::class)->reply([
            'mock_mode' => false,
            'message' => '今天要穿什麼？',
            'closet_context' => [
                'items' => [
                    ['name' => '白襯衫'],
                    ['name' => '深色外套'],
                ],
            ],
        ]);

        $this->assertSame('今天可以用白襯衫搭配深色外套。', $result['reply']);
        $this->assertSame('ready', $result['chat_generation']['status']);
        $this->assertSame('real_adapter', $result['chat_generation']['mode']);
        $this->assertFalse($result['chat_generation']['fallback_active']);
        $this->assertStringNotContainsString('test-gemini-key', json_encode($result, JSON_THROW_ON_ERROR));

        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-gemini-key')
            && str_contains($request->url(), '/v1beta/models/gemini-2.5-flash:generateContent'));
    }

    public function test_gemini_text_understanding_can_extract_ready_response(): void
    {
        Config::set('ai.gemini_api_key', 'test-gemini-key');
        Config::set('ai.text_understanding_model', 'gemini-2.5-flash');
        Config::set('ai.gemini_api_base_url', 'https://generativelanguage.googleapis.com');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'intent' => 'search_clothing',
                                        'normalized_query' => 'white shirt',
                                        'entities' => ['white', 'shirt'],
                                        'categories' => ['shirt'],
                                        'colors' => ['white'],
                                        'style_tags' => ['minimal'],
                                        'confidence' => 0.91,
                                        'safety_flags' => [],
                                    ], JSON_UNESCAPED_UNICODE),
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $result = app(GeminiTextUnderstandingService::class)->understand([
            'mock_mode' => false,
            'text' => '找白色襯衫',
        ]);

        $this->assertSame('search_clothing', $result['intent']);
        $this->assertSame('white shirt', $result['normalized_query']);
        $this->assertSame(['white'], $result['colors']);
        $this->assertSame('ready', $result['understanding_generation']['status']);
        $this->assertSame('real_adapter', $result['understanding_generation']['mode']);
        $this->assertFalse($result['understanding_generation']['fallback_active']);
        $this->assertStringNotContainsString('test-gemini-key', json_encode($result, JSON_THROW_ON_ERROR));
    }
}
