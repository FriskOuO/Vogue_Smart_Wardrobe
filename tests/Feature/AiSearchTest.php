<?php

namespace Tests\Feature;

use App\Models\Clothing;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AiSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_cannot_access_ai_search_page(): void
    {
        $response = $this->get('/closet/ai-search');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_ai_search_page(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/closet/ai-search');

        $response->assertStatus(200);
        $response->assertSee('AI 搜尋');
    }

    public function test_ai_search_page_accepts_empty_query(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/closet/ai-search');

        $response->assertStatus(200);
        $response->assertSee('搜尋模式');
    }

    public function test_keyword_fallback_can_find_clothing_by_name_when_ai_is_unavailable(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        Clothing::create([
            'user_id' => $user->id,
            'name' => 'White Test Shirt',
            'image_path' => 'clothes/test-shirt.jpg',
            'image_url' => '/storage/clothes/test-shirt.jpg',
            'category' => 'shirt',
            'subcategory' => 'top',
            'color' => 'white',
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        config([
            'ai.service_url' => 'http://127.0.0.1:9999',
        ]);

        $response = $this->actingAs($user)->get('/closet/ai-search?q=White');

        $response->assertStatus(200);
        $response->assertSee('keyword_fallback');
        $response->assertSee('關鍵字備援');
        $response->assertSee('White Test Shirt');
    }

    public function test_keyword_fallback_runs_when_ai_results_do_not_match_user_clothes(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        Clothing::create([
            'user_id' => $user->id,
            'name' => 'Codex Demo White Shirt',
            'image_path' => 'clothes/codex-demo-white-shirt.png',
            'image_url' => '/storage/clothes/codex-demo-white-shirt.png',
            'category' => 'shirt',
            'subcategory' => 'top',
            'color' => 'white',
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $this->mock(AiService::class, function ($mock) {
            $mock->shouldReceive('embedText')
                ->once()
                ->andReturn([
                    'status' => 'degraded',
                    'embedding' => [0.1, 0.2],
                ]);

            $mock->shouldReceive('searchSimilar')
                ->once()
                ->andReturn([
                    'status' => 'degraded',
                    'search_provider' => 'mock',
                    'results' => [
                        [
                            'clothing_id' => 999,
                            'score' => 0.75,
                            'reason' => 'mock result outside current user closet',
                        ],
                    ],
                ]);
        });

        $response = $this->actingAs($user)->get('/closet/ai-search?q=Codex%20Demo');

        $response->assertStatus(200);
        $response->assertSee('mock_empty_keyword_fallback');
        $response->assertSee('關鍵字備援');
        $response->assertSee('Codex Demo White Shirt');
    }

    public function test_ai_search_displays_similarity_metadata_for_vector_results(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $clothing = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Metadata Black Coat',
            'image_path' => 'clothes/metadata-black-coat.png',
            'image_url' => '/storage/clothes/metadata-black-coat.png',
            'category' => 'outerwear',
            'subcategory' => 'coat',
            'color' => 'black',
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $this->mock(AiService::class, function ($mock) use ($clothing) {
            $mock->shouldReceive('embedText')
                ->once()
                ->andReturn([
                    'status' => 'degraded',
                    'embedding' => [0.1, 0.2, 0.3],
                    'model' => 'mock-text-embedding',
                ]);

            $mock->shouldReceive('searchSimilar')
                ->once()
                ->andReturn([
                    'status' => 'degraded',
                    'search_provider' => 'mock_sqlite_fallback',
                    'target_search_provider' => 'qdrant',
                    'query_model' => 'mock-text-embedding',
                    'embedding_provider' => [
                        'target_provider' => 'clip',
                        'active_provider' => 'mock_embedding_fallback',
                        'adapter' => 'clip-embedding-v1',
                        'fallback_active' => true,
                        'status' => 'planned',
                    ],
                    'vector_store' => [
                        'adapter' => 'qdrant-vector-store-v1',
                        'target_provider' => 'qdrant',
                        'active_provider' => 'mock_sqlite_fallback',
                        'fallback_active' => true,
                        'status' => 'planned',
                    ],
                    'results' => [
                        [
                            'clothing_id' => $clothing->id,
                            'score' => 0.82,
                            'reason' => 'strong semantic match',
                            'vector_provider' => 'mock_sqlite_fallback',
                            'target_vector_provider' => 'qdrant',
                            'match_type' => 'vector_similarity_fallback',
                        ],
                    ],
                ]);
        });

        $response = $this->actingAs($user)->get('/closet/ai-search?q=black%20coat');

        $response->assertOk();
        $response->assertSee('Metadata Black Coat');
        $response->assertSee('搜尋資訊');
        $response->assertSee('排序 #1');
        $response->assertSee('來源：mock_sqlite_fallback');
        $response->assertSee('目標：qdrant');
        $response->assertSee('模型：mock-text-embedding');
        $response->assertSee('比對：vector_similarity_fallback');
        $response->assertSee('信心：medium');
        $response->assertSee('轉接器：qdrant-vector-store-v1');
        $response->assertSee('備援：啟用');
        $response->assertSee('向量目標：clip');
        $response->assertSee('向量轉接器：clip-embedding-v1');
        $response->assertSee('向量備援：啟用');
        $response->assertSee('82%');
    }

    public function test_ai_search_accepts_ready_qdrant_vector_results(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $clothing = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Ready Qdrant White Shirt',
            'image_path' => 'clothes/ready-qdrant-white-shirt.png',
            'image_url' => '/storage/clothes/ready-qdrant-white-shirt.png',
            'category' => 'shirt',
            'subcategory' => 'top',
            'color' => 'white',
            'ai_status' => 'ready',
            'ai_mode' => 'real_adapter',
        ]);

        $this->mock(AiService::class, function ($mock) use ($clothing) {
            $mock->shouldReceive('embedText')
                ->once()
                ->andReturn([
                    'status' => 'ready',
                    'mode' => 'real_adapter',
                    'embedding' => array_fill(0, 512, 0.02),
                    'model' => 'clip-vit-base-patch32',
                ]);

            $mock->shouldReceive('searchSimilar')
                ->once()
                ->andReturn([
                    'status' => 'ready',
                    'mode' => 'real_adapter',
                    'search_provider' => 'qdrant',
                    'target_search_provider' => 'qdrant',
                    'query_model' => 'clip-vit-base-patch32',
                    'embedding_provider' => [
                        'target_provider' => 'clip',
                        'active_provider' => 'clip',
                        'adapter' => 'clip-embedding-v1',
                        'fallback_active' => false,
                        'status' => 'ready',
                    ],
                    'vector_store' => [
                        'adapter' => 'qdrant-vector-store-v1',
                        'target_provider' => 'qdrant',
                        'active_provider' => 'qdrant',
                        'fallback_active' => false,
                        'status' => 'ready',
                    ],
                    'results' => [
                        [
                            'clothing_id' => $clothing->id,
                            'score' => 0.93,
                            'reason' => 'Qdrant real vector match',
                            'vector_provider' => 'qdrant',
                            'target_vector_provider' => 'qdrant',
                            'model' => 'clip-vit-base-patch32',
                            'match_type' => 'qdrant_vector_similarity',
                        ],
                    ],
                ]);
        });

        $response = $this->actingAs($user)->get('/closet/ai-search?q=white%20shirt&provider_mode=real');

        $response->assertOk();
        $response->assertSee('真實搜尋可人工驗收');
        $response->assertSee('fallback 未啟用');
        $response->assertSee('Ready Qdrant White Shirt');
        $response->assertSee('qdrant');
        $response->assertSee('clip-vit-base-patch32');
        $response->assertSee('qdrant_vector_similarity');
        $response->assertSee('93%');
    }

    public function test_real_provider_mode_sends_mock_mode_false_to_ai_service(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $clothing = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Real Mode White Shirt',
            'image_path' => 'clothes/real-mode-white-shirt.png',
            'image_url' => '/storage/clothes/real-mode-white-shirt.png',
            'category' => 'shirt',
            'subcategory' => 'top',
            'color' => 'white',
            'ai_status' => 'ready',
            'ai_mode' => 'real_adapter',
        ]);

        $this->mock(AiService::class, function ($mock) use ($clothing) {
            $mock->shouldReceive('embedText')
                ->once()
                ->with(Mockery::on(fn (array $payload) => ($payload['mock_mode'] ?? null) === false))
                ->andReturn([
                    'status' => 'ready',
                    'mode' => 'real_adapter',
                    'embedding' => array_fill(0, 512, 0.02),
                    'model' => 'clip-vit-base-patch32',
                ]);

            $mock->shouldReceive('searchSimilar')
                ->once()
                ->with(Mockery::on(fn (array $payload) => ($payload['mock_mode'] ?? null) === false))
                ->andReturn([
                    'status' => 'ready',
                    'mode' => 'real_adapter',
                    'search_provider' => 'qdrant',
                    'target_search_provider' => 'qdrant',
                    'query_model' => 'clip-vit-base-patch32',
                    'embedding_provider' => [
                        'target_provider' => 'clip',
                        'active_provider' => 'clip',
                        'adapter' => 'clip-embedding-v1',
                        'fallback_active' => false,
                        'status' => 'ready',
                    ],
                    'vector_store' => [
                        'adapter' => 'qdrant-vector-store-v1',
                        'target_provider' => 'qdrant',
                        'active_provider' => 'qdrant',
                        'fallback_active' => false,
                        'status' => 'ready',
                    ],
                    'results' => [
                        [
                            'clothing_id' => $clothing->id,
                            'score' => 0.91,
                            'reason' => 'Real provider mode result',
                            'vector_provider' => 'qdrant',
                            'target_vector_provider' => 'qdrant',
                            'model' => 'clip-vit-base-patch32',
                            'match_type' => 'qdrant_vector_similarity',
                        ],
                    ],
                ]);
        });

        $response = $this->actingAs($user)->get('/closet/ai-search?q=white%20shirt&provider_mode=real');

        $response->assertOk();
        $response->assertSee('真實搜尋可人工驗收');
        $response->assertSee('fallback 未啟用');
        $response->assertSee('Real Mode White Shirt');
        $response->assertSee('真實模型');
    }
}
