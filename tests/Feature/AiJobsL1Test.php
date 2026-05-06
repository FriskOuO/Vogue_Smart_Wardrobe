<?php

namespace Tests\Feature;

use App\Models\AiJob;
use App\Models\Clothing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiJobsL1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_authenticated_user_can_access_tryon_page(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/closet/try-on');

        $response->assertStatus(200);
        $response->assertSee('Virtual Try-on L1');
    }

    public function test_authenticated_user_can_access_runway_video_workspace(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/workspace/runway-video');

        $response->assertStatus(200);
        $response->assertSee('Runway Video');
    }

    public function test_authenticated_user_can_access_digital_twin_workspace(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/workspace/digital-twin');

        $response->assertStatus(200);
        $response->assertSee('Digital Twin');
    }

    public function test_runway_video_l1_creates_ai_job(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $clothing = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Runway Test Shirt',
            'image_path' => 'clothes/runway-test-shirt.jpg',
            'image_url' => '/storage/clothes/runway-test-shirt.jpg',
            'category' => '上衣',
            'color' => '白色',
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $response = $this->actingAs($user)->post(route('workspace.runway-video.store'), [
            'clothing_id' => $clothing->id,
            'video_style' => 'vogue luxury runway',
            'camera_rhythm' => 'slow cinematic camera movement',
        ]);

        $response->assertRedirect(route('workspace.show', 'runway-video'));

        $this->assertDatabaseHas('ai_jobs', [
            'user_id' => $user->id,
            'clothing_id' => $clothing->id,
            'job_type' => 'runway_video',
            'status' => 'degraded',
            'mode' => 'mock',
        ]);

        $job = AiJob::where('job_type', 'runway_video')->latest()->first();

        $this->assertNotNull($job);
        $this->assertIsArray($job->result_json);
        $this->assertArrayHasKey('prompt', $job->result_json);
        $this->assertArrayHasKey('scenes', $job->result_json);
    }

    public function test_digital_twin_l1_creates_ai_job(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->post(route('workspace.digital-twin.store'), [
            'height_cm' => 170,
            'style_preference' => '簡約都會風',
            'common_occasion' => '校園日常',
            'body_note' => '喜歡寬鬆版型',
        ]);

        $response->assertRedirect(route('workspace.show', 'digital-twin'));

        $this->assertDatabaseHas('ai_jobs', [
            'user_id' => $user->id,
            'job_type' => 'digital_twin',
            'status' => 'degraded',
            'mode' => 'mock',
        ]);

        $job = AiJob::where('job_type', 'digital_twin')->latest()->first();

        $this->assertNotNull($job);
        $this->assertIsArray($job->result_json);
        $this->assertArrayHasKey('profile', $job->result_json);
        $this->assertArrayHasKey('style_summary', $job->result_json);
        $this->assertArrayHasKey('style_tags', $job->result_json);
    }

    public function test_digital_twin_l2_requires_clothing_items(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->post(route('workspace.digital-twin.analyze-closet'));

        $response->assertRedirect(route('workspace.show', 'digital-twin'));
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('ai_jobs', [
            'user_id' => $user->id,
            'job_type' => 'digital_twin_style_analysis',
        ]);
    }

    public function test_digital_twin_l2_creates_closet_style_analysis_job(): void
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
            'season' => ['春', '秋'],
            'occasion' => ['校園日常'],
            'usage' => ['日常穿搭'],
            'style_tags' => ['簡約', '俐落'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $response = $this->actingAs($user)->post(route('workspace.digital-twin.analyze-closet'));

        $response->assertRedirect(route('workspace.show', 'digital-twin'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('ai_jobs', [
            'user_id' => $user->id,
            'job_type' => 'digital_twin_style_analysis',
            'status' => 'degraded',
            'mode' => 'rule_based',
        ]);

        $job = AiJob::where('job_type', 'digital_twin_style_analysis')->latest()->first();

        $this->assertNotNull($job);
        $this->assertIsArray($job->result_json);
        $this->assertArrayHasKey('closet_statistics', $job->result_json);
        $this->assertArrayHasKey('top_categories', $job->result_json['closet_statistics']);
        $this->assertArrayHasKey('top_colors', $job->result_json['closet_statistics']);
        $this->assertArrayHasKey('top_style_tags', $job->result_json['closet_statistics']);
        $this->assertEquals('上衣', $job->result_json['closet_statistics']['top_categories'][0]['label']);
    }

    public function test_digital_twin_l2_only_uses_current_users_clothes(): void
    {
        $currentUser = User::factory()->create([
            'role' => 'user',
        ]);

        $otherUser = User::factory()->create([
            'role' => 'user',
        ]);

        Clothing::create([
            'user_id' => $currentUser->id,
            'name' => '白色簡約襯衫',
            'image_path' => 'clothes/current-user-shirt.jpg',
            'image_url' => '/storage/clothes/current-user-shirt.jpg',
            'category' => '上衣',
            'subcategory' => '襯衫',
            'color' => '白色',
            'season' => ['春', '夏'],
            'occasion' => ['校園日常'],
            'usage' => ['日常穿搭'],
            'style_tags' => ['簡約'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        Clothing::create([
            'user_id' => $otherUser->id,
            'name' => '紅色派對洋裝',
            'image_path' => 'clothes/other-user-dress.jpg',
            'image_url' => '/storage/clothes/other-user-dress.jpg',
            'category' => '洋裝',
            'subcategory' => '禮服',
            'color' => '紅色',
            'season' => ['夏'],
            'occasion' => ['派對'],
            'usage' => ['正式場合'],
            'style_tags' => ['華麗'],
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $response = $this->actingAs($currentUser)->post(route('workspace.digital-twin.analyze-closet'));

        $response->assertRedirect(route('workspace.show', 'digital-twin'));
        $response->assertSessionHas('status');

        $job = AiJob::where('user_id', $currentUser->id)
            ->where('job_type', 'digital_twin_style_analysis')
            ->latest()
            ->first();

        $this->assertNotNull($job);
        $this->assertIsArray($job->result_json);

        $statistics = $job->result_json['closet_statistics'];

        $categoryLabels = collect($statistics['top_categories'])->pluck('label')->all();
        $colorLabels = collect($statistics['top_colors'])->pluck('label')->all();
        $styleLabels = collect($statistics['top_style_tags'])->pluck('label')->all();

        $this->assertContains('上衣', $categoryLabels);
        $this->assertContains('白色', $colorLabels);
        $this->assertContains('簡約', $styleLabels);

        $this->assertNotContains('洋裝', $categoryLabels);
        $this->assertNotContains('紅色', $colorLabels);
        $this->assertNotContains('華麗', $styleLabels);
    }
}