<?php

namespace Tests\Feature;

use App\Models\Clothing;
use App\Models\StylistHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response->assertSee('智慧穿搭推薦');
    }

    public function test_user_without_clothes_cannot_generate_stylist_recommendation(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->post(route('closet.stylist.generate'), [
            'occasion' => '校園日常',
            'weather' => '晴天 24°C',
            'style_preference' => '簡約都會風',
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
            'name' => '白色簡約襯衫',
            'image_path' => 'clothes/test-white-shirt.jpg',
            'image_url' => '/storage/clothes/test-white-shirt.jpg',
            'category' => '上衣',
            'subcategory' => '襯衫',
            'color' => '白色',
            'season' => ['春', '夏'],
            'occasion' => ['校園日常', '通勤'],
            'usage' => ['日常穿搭'],
            'style_tags' => ['簡約', '都會'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        Clothing::create([
            'user_id' => $user->id,
            'name' => '黑色直筒長褲',
            'image_path' => 'clothes/test-black-pants.jpg',
            'image_url' => '/storage/clothes/test-black-pants.jpg',
            'category' => '下身',
            'subcategory' => '長褲',
            'color' => '黑色',
            'season' => ['春', '夏', '秋'],
            'occasion' => ['校園日常', '通勤'],
            'usage' => ['日常穿搭'],
            'style_tags' => ['簡約', '俐落'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $response = $this->actingAs($user)->post(route('closet.stylist.generate'), [
            'occasion' => '校園日常',
            'weather' => '晴天 24°C',
            'style_preference' => '簡約都會風',
        ]);

        $response->assertRedirect(route('closet.stylist'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('stylist_history', [
            'user_id' => $user->id,
            'occasion' => '校園日常',
            'weather' => '晴天 24°C',
            'style_preference' => '簡約都會風',
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
            'occasion' => '約會',
            'weather' => '晚上偏涼',
            'style_preference' => '韓系休閒',
            'selected_items' => [
                [
                    'id' => 1,
                    'name' => 'Owner Private Outfit',
                ],
            ],
            'recommendation_json' => [
                'title' => 'Owner Private Recommendation',
                'summary' => '這是其他使用者的推薦紀錄。',
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