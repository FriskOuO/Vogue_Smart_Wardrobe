<?php

namespace Tests\Feature;

use App\Services\AiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiServiceMockModeTest extends TestCase
{
    public function test_ai_service_sends_configured_mock_mode_false_to_adapter_endpoints(): void
    {
        config([
            'ai.service_url' => 'http://ai-service.test',
            'ai.internal_token' => 'test-token',
            'ai.mock_mode' => false,
        ]);

        Http::fake([
            'ai-service.test/*' => Http::response([
                'schema_version' => 'v1',
                'status' => 'degraded',
            ]),
        ]);

        $aiService = new AiService();

        $aiService->analyzeAttributes([
            'request_id' => 'attributes-test',
            'user_id' => 11,
            'clothing_id' => 22,
            'image_path' => 'clothes/11/item.jpg',
            'image_url' => 'http://app.test/storage/clothes/11/item.jpg',
        ]);

        $aiService->embedImage([
            'request_id' => 'embed-image-test',
            'user_id' => 11,
            'clothing_id' => 22,
            'image_path' => 'clothes/11/item.jpg',
            'image_url' => 'http://app.test/storage/clothes/11/item.jpg',
        ]);

        $aiService->embedText([
            'request_id' => 'embed-text-test',
            'user_id' => 11,
            'query' => 'white shirt',
        ]);

        $aiService->searchSimilar([
            'request_id' => 'search-test',
            'user_id' => 11,
            'query_type' => 'text',
            'query' => 'white shirt',
            'embedding' => [0.1, 0.2],
        ]);

        Http::assertSentCount(4);

        Http::assertSent(
            fn ($request) => $request->url() === 'http://ai-service.test/ai/attributes'
                && $request['mock_mode'] === false
                && $request->hasHeader('X-Internal-AI-Token', 'test-token')
        );

        Http::assertSent(
            fn ($request) => $request->url() === 'http://ai-service.test/ai/embed/image'
                && $request['mock_mode'] === false
                && $request['store_to_vector_db'] === true
        );

        Http::assertSent(
            fn ($request) => $request->url() === 'http://ai-service.test/ai/embed/text'
                && $request['mock_mode'] === false
                && $request['model'] === 'clip-vit-base-patch32'
        );

        Http::assertSent(
            fn ($request) => $request->url() === 'http://ai-service.test/ai/search/similar'
                && $request['mock_mode'] === false
                && $request['fallback_enabled'] === true
        );
    }

    public function test_ai_service_allows_per_request_mock_mode_override(): void
    {
        config([
            'ai.service_url' => 'http://ai-service.test',
            'ai.internal_token' => 'test-token',
            'ai.mock_mode' => false,
        ]);

        Http::fake([
            'ai-service.test/*' => Http::response([
                'schema_version' => 'v1',
                'status' => 'degraded',
            ]),
        ]);

        $aiService = new AiService();

        $aiService->embedText([
            'request_id' => 'embed-text-override-test',
            'user_id' => 11,
            'query' => 'white shirt',
            'mock_mode' => true,
        ]);

        Http::assertSent(
            fn ($request) => $request->url() === 'http://ai-service.test/ai/embed/text'
                && $request['mock_mode'] === true
        );
    }
}
