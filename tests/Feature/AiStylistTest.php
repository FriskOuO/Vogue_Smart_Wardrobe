<?php

namespace Tests\Feature;

use App\Models\Clothing;
use App\Models\AiEmbedding;
use App\Models\AiJob;
use App\Models\OutfitLog;
use App\Models\StylistHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiStylistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_cannot_access_ai_stylist_page(): void
    {
        $response = $this->get('/closet/stylist');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_ai_stylist_page(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/closet/stylist');

        $response->assertStatus(200);
        $response->assertSee('依照場合產生穿搭建議');
    }

    public function test_user_without_clothes_cannot_generate_stylist_recommendation(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->post(route('closet.stylist.generate'), [
            'occasion' => '?∪??亙虜',
            'weather' => '?游予 24簞C',
            'style_preference' => '簡約乾淨',
        ]);

        $response->assertRedirect(route('closet.stylist'));
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('stylist_history', 0);
    }

    public function test_user_with_clothes_can_generate_stylist_recommendation(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        Clothing::create([
            'user_id' => $user->id,
            'name' => 'White Minimal Shirt',
            'image_path' => 'clothes/test-white-shirt.jpg',
            'image_url' => '/storage/clothes/test-white-shirt.jpg',
            'category' => 'shirt',
            'subcategory' => 'top',
            'color' => 'white',
            'season' => ['spring', 'summer'],
            'occasion' => ['daily', 'work'],
            'usage' => ['casual'],
            'style_tags' => ['minimal', 'clean'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        Clothing::create([
            'user_id' => $user->id,
            'name' => 'Black Tailored Pants',
            'image_path' => 'clothes/test-black-pants.jpg',
            'image_url' => '/storage/clothes/test-black-pants.jpg',
            'category' => 'pants',
            'subcategory' => 'trousers',
            'color' => 'black',
            'season' => ['spring', 'summer', 'autumn'],
            'occasion' => ['daily', 'work'],
            'usage' => ['casual'],
            'style_tags' => ['minimal', 'polished'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $response = $this->actingAs($user)->post(route('closet.stylist.generate'), [
            'occasion' => 'daily',
            'weather' => 'comfortable 24C',
            'style_preference' => '簡約乾淨',
        ]);

        $response->assertRedirect(route('closet.stylist'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('stylist_history', [
            'user_id' => $user->id,
            'occasion' => 'daily',
            'weather' => 'comfortable 24C',
            'style_preference' => '簡約乾淨',
            'status' => 'degraded',
            'mode' => 'rule_based',
        ]);

        $history = StylistHistory::latest()->first();

        $this->assertNotNull($history);
        $this->assertIsArray($history->selected_items);
        $this->assertIsArray($history->recommendation_json);
        $this->assertNotEmpty($history->selected_items);
        $this->assertArrayHasKey('title', $history->recommendation_json);
        $this->assertArrayHasKey('summary', $history->recommendation_json);
        $this->assertArrayHasKey('reasoning', $history->recommendation_json);
    }

    public function test_stylist_uses_latest_digital_twin_closet_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $coat = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Black Minimal Coat',
            'image_path' => 'clothes/black-minimal-coat.jpg',
            'image_url' => '/storage/clothes/black-minimal-coat.jpg',
            'category' => 'outerwear',
            'color' => 'black',
            'season' => ['winter'],
            'occasion' => ['daily'],
            'usage' => ['layering'],
            'style_tags' => ['minimal'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        Clothing::create([
            'user_id' => $user->id,
            'name' => 'Blue Sport Shirt',
            'image_path' => 'clothes/blue-sport-shirt.jpg',
            'image_url' => '/storage/clothes/blue-sport-shirt.jpg',
            'category' => 'shirt',
            'color' => 'blue',
            'season' => ['summer'],
            'occasion' => ['gym'],
            'usage' => ['sport'],
            'style_tags' => ['sport'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        AiJob::create([
            'user_id' => $user->id,
            'clothing_id' => null,
            'job_type' => 'digital_twin_style_analysis',
            'status' => 'degraded',
            'mode' => 'rule_based',
            'request_id' => 'digital-twin-stylist-test',
            'input_json' => [
                'source' => 'clothes',
                'total_items' => 2,
            ],
            'result_json' => [
                'profile' => [
                    'source' => 'clothes',
                    'total_items' => 2,
                    'dominant_category' => 'outerwear',
                    'dominant_color' => 'black',
                    'dominant_occasion' => 'daily',
                    'dominant_style' => 'minimal',
                ],
                'style_summary' => [
                    'headline' => 'Minimal black outerwear profile',
                ],
                'closet_statistics' => [
                    'top_categories' => [
                        ['label' => 'outerwear', 'count' => 1],
                    ],
                    'top_colors' => [
                        ['label' => 'black', 'count' => 1],
                    ],
                    'top_style_tags' => [
                        ['label' => 'minimal', 'count' => 1],
                    ],
                ],
            ],
            'degraded_reason' => 'DIGITAL_TWIN_RULE_BASED_CLOSET_ANALYSIS',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('closet.stylist.generate'), [
            'occasion' => 'gallery opening',
            'weather' => 'cool evening',
            'style_preference' => 'quiet luxury',
        ]);

        $response->assertRedirect(route('closet.stylist'));
        $response->assertSessionHas('status');

        $history = StylistHistory::latest()->first();

        $this->assertNotNull($history);
        $this->assertIsArray($history->recommendation_json);
        $this->assertSame('outerwear', $history->recommendation_json['digital_twin_profile']['dominant_category']);
        $this->assertSame('black', $history->recommendation_json['digital_twin_profile']['dominant_color']);
        $this->assertSame('minimal', $history->recommendation_json['digital_twin_profile']['dominant_style']);
        $this->assertContains($coat->id, collect($history->selected_items)->pluck('id')->all());
        $this->assertTrue(
            collect($history->recommendation_json['reasoning'])->contains(
                fn ($reason) => str_contains($reason, '數位分身 L2 衣櫥資料')
            )
        );

        $page = $this->actingAs($user)->get(route('closet.stylist'));

        $page->assertOk();
        $page->assertSee('數位分身資料');
        $page->assertSee('已使用的數位分身資料');
        $page->assertSee('outerwear');
        $page->assertSee('minimal');
    }

    public function test_stylist_saves_expanded_context_inputs(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $coat = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Winter Black Coat',
            'image_path' => 'clothes/winter-black-coat.jpg',
            'image_url' => '/storage/clothes/winter-black-coat.jpg',
            'category' => 'outerwear',
            'color' => 'black',
            'season' => ['winter'],
            'occasion' => ['dinner'],
            'usage' => ['layering'],
            'style_tags' => ['elegant'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $redDress = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Red Dinner Dress',
            'image_path' => 'clothes/red-dinner-dress.jpg',
            'image_url' => '/storage/clothes/red-dinner-dress.jpg',
            'category' => 'dress',
            'color' => 'red',
            'season' => ['winter'],
            'occasion' => ['dinner'],
            'usage' => ['event'],
            'style_tags' => ['formal'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $response = $this->actingAs($user)->post(route('closet.stylist.generate'), [
            'occasion' => 'dinner',
            'weather' => 'cold evening',
            'season_context' => 'winter',
            'formality_level' => 'smart casual',
            'mood_context' => 'confident',
            'style_preference' => 'elegant black outfit',
            'avoid_notes' => 'avoid red',
        ]);

        $response->assertRedirect(route('closet.stylist'));
        $response->assertSessionHas('status');

        $history = StylistHistory::latest()->first();

        $this->assertNotNull($history);
        $this->assertSame('winter', $history->context_json['season_context']);
        $this->assertSame('smart casual', $history->context_json['formality_level']);
        $this->assertSame('confident', $history->context_json['mood_context']);
        $this->assertSame('avoid red', $history->context_json['avoid_notes']);
        $this->assertSame('winter', $history->recommendation_json['context']['season_context']);
        $this->assertContains($coat->id, collect($history->selected_items)->pluck('id')->all());
        $this->assertNotContains($redDress->id, collect($history->selected_items)->pluck('id')->all());
        $this->assertTrue(
            collect($history->recommendation_json['reasoning'])->contains(
                fn ($reason) => str_contains($reason, '正式程度')
            )
        );

        $page = $this->actingAs($user)->get(route('closet.stylist'));

        $page->assertOk();
        $page->assertSee('正式程度');
        $page->assertSee('smart casual');
        $page->assertSee('confident');
        $page->assertSee('avoid red');
    }

    public function test_stylist_uses_ai_embeddings_to_rank_candidates(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $embeddingMatch = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Embedding Match Coat',
            'image_path' => 'clothes/embedding-match-coat.jpg',
            'image_url' => '/storage/clothes/embedding-match-coat.jpg',
            'category' => 'outerwear',
            'color' => 'black',
            'season' => ['winter'],
            'occasion' => ['gallery'],
            'usage' => ['layering'],
            'style_tags' => ['elegant'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $sportItem = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Sport Red Shirt',
            'image_path' => 'clothes/sport-red-shirt.jpg',
            'image_url' => '/storage/clothes/sport-red-shirt.jpg',
            'category' => 'shirt',
            'color' => 'red',
            'season' => ['summer'],
            'occasion' => ['gym'],
            'usage' => ['sport'],
            'style_tags' => ['sport'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        AiEmbedding::create([
            'user_id' => $user->id,
            'clothing_id' => $embeddingMatch->id,
            'embedding_type' => 'image',
            'source_type' => 'clothing',
            'model' => 'mock-clip',
            'vector_dimension' => 8,
            'embedding' => [0.4, 0, 0.8, 0, 0.4, 0, 0, 0.8],
            'embedding_preview' => [0.4, 0, 0.8],
            'vector_provider' => 'mock',
            'vector_stored' => true,
            'status' => 'degraded',
            'mode' => 'mock',
        ]);

        AiEmbedding::create([
            'user_id' => $user->id,
            'clothing_id' => $sportItem->id,
            'embedding_type' => 'image',
            'source_type' => 'clothing',
            'model' => 'mock-clip',
            'vector_dimension' => 8,
            'embedding' => [0, 1, 0, 1, 0, 1, 0, 0],
            'embedding_preview' => [0, 1, 0],
            'vector_provider' => 'mock',
            'vector_stored' => true,
            'status' => 'degraded',
            'mode' => 'mock',
        ]);

        $response = $this->actingAs($user)->post(route('closet.stylist.generate'), [
            'occasion' => 'gallery dinner',
            'weather' => 'cold evening',
            'season_context' => 'winter',
            'formality_level' => 'elegant smart',
            'mood_context' => 'confident',
            'style_preference' => 'black elegant outfit',
            'avoid_notes' => '',
        ]);

        $response->assertRedirect(route('closet.stylist'));
        $response->assertSessionHas('status');

        $history = StylistHistory::latest()->first();

        $this->assertNotNull($history);
        $this->assertSame('local_cosine', $history->recommendation_json['embedding_signals']['mode']);
        $this->assertSame(
            $embeddingMatch->id,
            $history->recommendation_json['embedding_signals']['top_matches'][0]['clothing_id']
        );
        $this->assertSame($embeddingMatch->id, $history->selected_items[0]['id']);
        $this->assertGreaterThan(0, $history->selected_items[0]['embedding_score']);
        $this->assertTrue(
            collect($history->recommendation_json['reasoning'])->contains(
                fn ($reason) => str_contains($reason, 'ai_embeddings')
            )
        );

        $page = $this->actingAs($user)->get(route('closet.stylist'));

        $page->assertOk();
        $page->assertSee('向量訊號');
        $page->assertSee('local_cosine');
        $page->assertSee('Embedding Match Coat');
        $page->assertSee('向量分數');
    }

    public function test_stylist_records_gemini_text_generation_adapter_plan(): void
    {
        config([
            'ai.text_generation_provider' => 'gemini',
            'ai.gemini_text_model' => 'gemini-test-placeholder',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        Clothing::create([
            'user_id' => $user->id,
            'name' => 'Adapter Black Jacket',
            'image_path' => 'clothes/adapter-black-jacket.jpg',
            'image_url' => '/storage/clothes/adapter-black-jacket.jpg',
            'category' => 'outerwear',
            'color' => 'black',
            'season' => ['winter'],
            'occasion' => ['dinner'],
            'usage' => ['layering'],
            'style_tags' => ['minimal'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $response = $this->actingAs($user)->post(route('closet.stylist.generate'), [
            'occasion' => 'client dinner',
            'weather' => 'cool evening',
            'season_context' => 'winter',
            'formality_level' => 'smart',
            'mood_context' => 'polished',
            'style_preference' => 'minimal black',
        ]);

        $response->assertRedirect(route('closet.stylist'));
        $response->assertSessionHas('status');

        $history = StylistHistory::latest()->first();

        $this->assertNotNull($history);
        $this->assertSame('gemini', $history->recommendation_json['text_generation']['provider']);
        $this->assertSame('gemini-stylist-text-v1', $history->recommendation_json['text_generation']['adapter']);
        $this->assertSame('planned', $history->recommendation_json['text_generation']['status']);
        $this->assertSame('mock', $history->recommendation_json['text_generation']['mode']);
        $this->assertSame('gemini-test-placeholder', $history->recommendation_json['text_generation']['model']);
        $this->assertSame('rule_based_text', $history->recommendation_json['text_generation']['fallback']);
        $this->assertContains('selected_items', $history->recommendation_json['text_generation']['prompt_contract']['inputs']);
        $this->assertContains('styling_tips', $history->recommendation_json['text_generation']['prompt_contract']['outputs']);
        $this->assertStringContainsString('mock Gemini text adapter', $history->recommendation_json['summary']);

        $page = $this->actingAs($user)->get(route('closet.stylist'));

        $page->assertOk();
        $page->assertSee('Gemini 文字轉接器');
        $page->assertSee('gemini-stylist-text-v1');
        $page->assertSee('rule_based_text');
    }

    public function test_stylist_real_provider_mode_degrades_when_gemini_key_is_missing(): void
    {
        config([
            'ai.gemini_api_key' => null,
            'ai.gemini_text_model' => 'gemini-2.5-flash',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        Clothing::create([
            'user_id' => $user->id,
            'name' => 'Real Mode White Shirt',
            'image_path' => 'clothes/real-mode-white-shirt.jpg',
            'image_url' => '/storage/clothes/real-mode-white-shirt.jpg',
            'category' => 'shirt',
            'color' => 'white',
            'season' => ['summer'],
            'occasion' => ['daily'],
            'style_tags' => ['minimal'],
            'ai_status' => 'ready',
            'ai_mode' => 'real_adapter',
        ]);

        $response = $this->actingAs($user)->post(route('closet.stylist.generate'), [
            'occasion' => 'daily',
            'weather' => 'warm day',
            'style_preference' => 'minimal white outfit',
            'provider_mode' => 'real',
        ]);

        $response->assertRedirect(route('closet.stylist'));
        $response->assertSessionHas('status');
        $response->assertSessionHas('status', 'AI 穿搭顧問已嘗試 Gemini 真實模型，但目前使用安全 fallback；請查看最新紀錄的錯誤碼。');

        $history = StylistHistory::latest()->first();

        $this->assertNotNull($history);
        $this->assertSame('degraded', $history->status);
        $this->assertSame('rule_based', $history->mode);
        $this->assertSame('real', $history->context_json['provider_mode']);
        $this->assertSame('degraded', $history->recommendation_json['text_generation']['status']);
        $this->assertSame('real_adapter_attempt', $history->recommendation_json['text_generation']['mode']);
        $this->assertSame('GEMINI_API_KEY_MISSING', $history->recommendation_json['text_generation']['error_code']);
        $this->assertTrue($history->recommendation_json['text_generation']['fallback_active']);
    }

    public function test_stylist_real_provider_mode_can_record_ready_gemini_response(): void
    {
        config([
            'ai.gemini_api_key' => 'test-gemini-key',
            'ai.gemini_text_model' => 'gemini-2.5-flash',
            'ai.gemini_api_base_url' => 'https://generativelanguage.googleapis.com',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'title' => 'Gemini Ready Outfit',
                                        'summary' => 'A real Gemini structured styling response.',
                                        'styling_tips' => [
                                            'Use the white shirt as the visual anchor.',
                                            'Keep accessories minimal.',
                                        ],
                                        'reasoning_notes' => [
                                            'Used only selected closet items.',
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        Clothing::create([
            'user_id' => $user->id,
            'name' => 'Gemini White Shirt',
            'image_path' => 'clothes/gemini-white-shirt.jpg',
            'image_url' => '/storage/clothes/gemini-white-shirt.jpg',
            'category' => 'shirt',
            'color' => 'white',
            'season' => ['summer'],
            'occasion' => ['daily'],
            'style_tags' => ['minimal'],
            'ai_status' => 'ready',
            'ai_mode' => 'real_adapter',
        ]);

        $response = $this->actingAs($user)->post(route('closet.stylist.generate'), [
            'occasion' => 'daily',
            'weather' => 'warm day',
            'style_preference' => 'minimal white outfit',
            'provider_mode' => 'real',
        ]);

        $response->assertRedirect(route('closet.stylist'));
        $response->assertSessionHas('status', 'AI 穿搭顧問已使用 Gemini 真實模型產生建議，最新紀錄已標示 ready / real_adapter。');

        $history = StylistHistory::latest()->first();

        $this->assertNotNull($history);
        $this->assertSame('ready', $history->status);
        $this->assertSame('real_adapter', $history->mode);
        $this->assertSame('Gemini Ready Outfit', $history->recommendation_json['title']);
        $this->assertSame('ready', $history->recommendation_json['text_generation']['status']);
        $this->assertSame('real_adapter', $history->recommendation_json['text_generation']['mode']);
        $this->assertFalse($history->recommendation_json['text_generation']['fallback_active']);
        $this->assertSame('/v1beta/models/gemini-2.5-flash:generateContent', $history->recommendation_json['text_generation']['endpoint']);

        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-gemini-key')
            && str_contains($request->url(), '/v1beta/models/gemini-2.5-flash:generateContent')
            && data_get($request->data(), 'generationConfig.responseMimeType') === 'application/json');

        $page = $this->actingAs($user)->get(route('closet.stylist'));

        $page->assertOk();
        $page->assertSee('Gemini Ready Outfit');
        $page->assertSee('real_adapter / ready');
        $page->assertSee('fallback_active: false');
    }

    public function test_user_can_save_stylist_feedback(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $history = StylistHistory::create([
            'user_id' => $user->id,
            'occasion' => 'gallery opening',
            'weather' => 'cool evening',
            'style_preference' => 'quiet luxury',
            'selected_items' => [
                [
                    'id' => 1,
                    'name' => 'Black Minimal Coat',
                ],
            ],
            'recommendation_json' => [
                'title' => 'Gallery opening recommendation',
                'summary' => 'A quiet luxury outfit.',
            ],
            'status' => 'degraded',
            'mode' => 'rule_based',
            'is_accepted' => false,
        ]);

        $likeResponse = $this->actingAs($user)->post(route('closet.stylist.feedback', $history->id), [
            'feedback_status' => 'liked',
        ]);

        $likeResponse->assertRedirect(route('closet.stylist'));
        $likeResponse->assertSessionHas('status');

        $history->refresh();

        $this->assertTrue($history->is_accepted);
        $this->assertSame('liked', $history->feedback_status);
        $this->assertNull($history->feedback_reason);
        $this->assertSame('ai_stylist_feedback', $history->feedback_json['source']);
        $this->assertNotNull($history->feedback_submitted_at);

        $rejectResponse = $this->actingAs($user)->post(route('closet.stylist.feedback', $history->id), [
            'feedback_status' => 'rejected',
            'feedback_reason' => 'Too formal for this occasion.',
        ]);

        $rejectResponse->assertRedirect(route('closet.stylist'));

        $history->refresh();

        $this->assertFalse($history->is_accepted);
        $this->assertSame('rejected', $history->feedback_status);
        $this->assertSame('Too formal for this occasion.', $history->feedback_reason);
        $this->assertSame('Too formal for this occasion.', $history->feedback_json['reason']);

        $page = $this->actingAs($user)->get(route('closet.stylist'));

        $page->assertOk();
        $page->assertSee('回饋');
        $page->assertSee('rejected');
        $page->assertSee('Too formal for this occasion.');
    }

    public function test_user_cannot_update_another_users_stylist_feedback(): void
    {
        $owner = User::factory()->create([
            'role' => 'user',
        ]);

        $otherUser = User::factory()->create([
            'role' => 'user',
        ]);

        $history = StylistHistory::create([
            'user_id' => $owner->id,
            'occasion' => 'private event',
            'weather' => 'cool evening',
            'style_preference' => 'minimal',
            'selected_items' => [],
            'recommendation_json' => [
                'title' => 'Owner private recommendation',
            ],
            'status' => 'degraded',
            'mode' => 'rule_based',
            'is_accepted' => false,
        ]);

        $response = $this->actingAs($otherUser)->post(route('closet.stylist.feedback', $history->id), [
            'feedback_status' => 'liked',
        ]);

        $response->assertNotFound();

        $history->refresh();

        $this->assertFalse($history->is_accepted);
        $this->assertNull($history->feedback_status);
    }

    public function test_user_can_save_stylist_history_as_outfit_log(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $history = StylistHistory::create([
            'user_id' => $user->id,
            'occasion' => 'gallery opening',
            'weather' => 'cool evening',
            'style_preference' => 'quiet luxury',
            'context_json' => [
                'season_context' => 'winter',
                'formality_level' => 'smart',
            ],
            'selected_items' => [
                [
                    'id' => 11,
                    'name' => 'Black Coat',
                    'category' => 'outerwear',
                ],
                [
                    'id' => 12,
                    'name' => 'White Shirt',
                    'category' => 'shirt',
                ],
            ],
            'recommendation_json' => [
                'title' => 'Gallery opening outfit',
                'summary' => 'A quiet luxury outfit.',
            ],
            'status' => 'degraded',
            'mode' => 'rule_based',
            'is_accepted' => false,
        ]);

        $response = $this->actingAs($user)->post(route('closet.stylist.outfit-log', $history->id), [
            'name' => 'Saved Gallery Outfit',
            'logged_at' => '2026-06-02T20:15',
            'notes' => 'Save this full outfit for later.',
        ]);

        $response->assertRedirect(route('closet.stylist'));
        $response->assertSessionHas('status');

        $outfitLog = OutfitLog::first();

        $this->assertNotNull($outfitLog);
        $this->assertSame($user->id, $outfitLog->user_id);
        $this->assertSame($history->id, $outfitLog->stylist_history_id);
        $this->assertSame('Saved Gallery Outfit', $outfitLog->name);
        $this->assertSame('gallery opening', $outfitLog->occasion);
        $this->assertSame('ai_stylist', $outfitLog->source);
        $this->assertSame([11, 12], $outfitLog->item_ids);
        $this->assertSame(2, $outfitLog->item_count);
        $this->assertSame('Save this full outfit for later.', $outfitLog->notes);
        $this->assertSame('closet.stylist', $outfitLog->metadata['source_route']);

        $page = $this->actingAs($user)->get(route('closet.stylist'));

        $page->assertOk();
        $page->assertSee('穿搭紀錄');
        $page->assertSee('1 筆已保存穿搭');
        $page->assertSee('保存穿搭紀錄');
    }

    public function test_user_cannot_save_another_users_stylist_history_as_outfit_log(): void
    {
        $owner = User::factory()->create([
            'role' => 'user',
        ]);

        $otherUser = User::factory()->create([
            'role' => 'user',
        ]);

        $history = StylistHistory::create([
            'user_id' => $owner->id,
            'occasion' => 'private event',
            'weather' => 'cool evening',
            'style_preference' => 'minimal',
            'selected_items' => [
                [
                    'id' => 1,
                    'name' => 'Private Coat',
                ],
            ],
            'recommendation_json' => [
                'title' => 'Private outfit',
            ],
            'status' => 'degraded',
            'mode' => 'rule_based',
            'is_accepted' => false,
        ]);

        $response = $this->actingAs($otherUser)->post(route('closet.stylist.outfit-log', $history->id), [
            'name' => 'Should not save',
        ]);

        $response->assertNotFound();
        $this->assertSame(0, OutfitLog::count());
    }

    public function test_stylist_history_is_isolated_by_user(): void
    {
        $owner = User::factory()->create([
            'role' => 'user',
        ]);

        $otherUser = User::factory()->create([
            'role' => 'user',
        ]);

        StylistHistory::create([
            'user_id' => $owner->id,
            'occasion' => '蝝?',
            'weather' => '???飲',
            'style_preference' => '?頂隡?',
            'selected_items' => [
                [
                    'id' => 1,
                    'name' => 'Owner Private Outfit',
                ],
            ],
            'recommendation_json' => [
                'title' => 'Owner Private Recommendation',
                'summary' => '此推薦只屬於擁有者。',
            ],
            'status' => 'degraded',
            'mode' => 'rule_based',
            'is_accepted' => false,
        ]);

        $response = $this->actingAs($otherUser)->get('/closet/stylist');

        $response->assertStatus(200);
        $response->assertDontSee('Owner Private Recommendation');
        $response->assertDontSee('Owner Private Outfit');
    }
}

