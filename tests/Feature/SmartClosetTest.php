<?php

namespace Tests\Feature;

use App\Models\Clothing;
use App\Models\User;
use App\Models\WearLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartClosetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
{
    parent::setUp();

    $this->withoutVite();
}

    public function test_guest_cannot_access_closet_index(): void
    {
        $response = $this->get('/closet');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_closet_index(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/closet');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_access_closet_create_page(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/closet/create');

        $response->assertStatus(200);
    }

    public function test_smart_closet_hub_displays_manual_acceptance_cockpit(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get(route('closet.hub'));

        $response->assertOk();
        $response->assertSee('人工驗收總控台');
        $response->assertSee('AI 搜尋真實模型');
        $response->assertSee('AI 穿搭顧問');
        $response->assertSee('試穿 / 姿態');
        $response->assertSee('伸展台影片');
        $response->assertSee('數位分身');
        $response->assertSee('真實搜尋可人工驗收');
        $response->assertSee('最新伸展台任務可人工驗收');
        $response->assertSee('最新數位分身任務可人工驗收');
    }

    public function test_closet_show_displays_user_owned_clothing(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $clothing = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Test White Shirt',
            'image_path' => 'clothes/test-white-shirt.jpg',
            'image_url' => '/storage/clothes/test-white-shirt.jpg',
            'category' => '上衣',
            'color' => '白色',
            'season' => ['春', '夏'],
            'occasion' => ['日常'],
            'usage' => ['休閒穿搭'],
            'style_tags' => ['簡約'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $response = $this->actingAs($user)->get(route('closet.show', $clothing->id));

        $response->assertStatus(200);
        $response->assertSee('Test White Shirt');
    }

    public function test_closet_show_displays_blip_caption_contract(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $clothing = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Caption Contract Shirt',
            'image_path' => 'clothes/caption-contract-shirt.jpg',
            'image_url' => '/storage/clothes/caption-contract-shirt.jpg',
            'category' => 'top',
            'color' => 'white',
            'season' => ['summer'],
            'occasion' => ['daily'],
            'usage' => ['casual'],
            'style_tags' => ['minimal'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
            'ai_raw_result' => [
                'image_caption' => [
                    'target_provider' => 'blip',
                    'active_provider' => 'mock_caption_fallback',
                    'adapter' => 'blip-image-caption-v1',
                    'target_model' => 'Salesforce/blip-image-captioning-base',
                    'fallback_active' => true,
                    'caption' => 'Mock caption: a clean wardrobe item photographed for style analysis.',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('closet.show', $clothing->id));

        $response->assertOk();
        $response->assertSee('影像描述合約');
        $response->assertSee('Mock caption: a clean wardrobe item photographed for style analysis.');
        $response->assertSee('mock_caption_fallback');
        $response->assertSee('blip-image-caption-v1');
        $response->assertSee('Salesforce/blip-image-captioning-base');
    }

    public function test_user_cannot_access_other_users_clothing(): void
    {
        $owner = User::factory()->create([
            'role' => 'user',
        ]);

        $otherUser = User::factory()->create([
            'role' => 'user',
        ]);

        $clothing = Clothing::create([
            'user_id' => $owner->id,
            'name' => 'Private Jacket',
            'image_path' => 'clothes/private-jacket.jpg',
            'image_url' => '/storage/clothes/private-jacket.jpg',
            'category' => '外套',
            'color' => '黑色',
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $response = $this->actingAs($otherUser)->get(route('closet.show', $clothing->id));

        $response->assertStatus(404);
    }

    public function test_user_can_record_wear_log_for_owned_clothing(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $clothing = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Wear Log Jacket',
            'image_path' => 'clothes/wear-log-jacket.jpg',
            'image_url' => '/storage/clothes/wear-log-jacket.jpg',
            'category' => 'outerwear',
            'color' => 'black',
            'wear_count' => 0,
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $wornAt = '2026-06-02T09:30';

        $response = $this->actingAs($user)->post(route('closet.wear.store', $clothing->id), [
            'worn_at' => $wornAt,
            'context' => 'work',
            'notes' => 'Worked well with denim.',
        ]);

        $response->assertRedirect(route('closet.show', $clothing->id));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('wear_logs', [
            'user_id' => $user->id,
            'clothing_id' => $clothing->id,
            'context' => 'work',
            'source' => 'manual',
            'notes' => 'Worked well with denim.',
        ]);

        $clothing->refresh();

        $this->assertSame(1, $clothing->wear_count);
        $this->assertTrue($clothing->last_worn_at->equalTo(Carbon::parse($wornAt)));

        $page = $this->actingAs($user)->get(route('closet.show', $clothing->id));

        $page->assertOk();
        $page->assertSee('穿著紀錄');
        $page->assertSee('1 次穿著');
        $page->assertSee('最近穿著紀錄');
        $page->assertSee('work');
        $page->assertSee('Worked well with denim.');
    }

    public function test_user_cannot_record_wear_log_for_another_users_clothing(): void
    {
        $owner = User::factory()->create([
            'role' => 'user',
        ]);

        $otherUser = User::factory()->create([
            'role' => 'user',
        ]);

        $clothing = Clothing::create([
            'user_id' => $owner->id,
            'name' => 'Private Wear Jacket',
            'image_path' => 'clothes/private-wear-jacket.jpg',
            'image_url' => '/storage/clothes/private-wear-jacket.jpg',
            'category' => 'outerwear',
            'color' => 'black',
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $response = $this->actingAs($otherUser)->post(route('closet.wear.store', $clothing->id), [
            'context' => 'work',
        ]);

        $response->assertNotFound();
        $this->assertSame(0, WearLog::count());
    }
}
