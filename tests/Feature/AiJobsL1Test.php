<?php

namespace Tests\Feature;

use App\Models\AiJob;
use App\Models\Clothing;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $response->assertSee('虛擬試穿');
    }

    public function test_tryon_l2_creates_pose_quality_job(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $clothing = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Try-on Test Jacket',
            'image_path' => 'clothes/tryon-test-jacket.jpg',
            'image_url' => '/storage/clothes/tryon-test-jacket.jpg',
            'category' => 'outerwear',
            'color' => 'black',
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $this->mock(AiService::class, function ($mock): void {
            $mock->shouldReceive('analyzePose')
                ->once()
                ->andReturn([
                    'schema_version' => 'v1',
                    'request_id' => 'pose-test-request',
                    'status' => 'degraded',
                    'mode' => 'mock',
                    'degraded_reason' => 'MOCK_POSE_ENABLED',
                    'pose_model' => 'mock-pose',
                    'pose_quality_score' => 0.86,
                    'pose_quality_status' => 'usable',
                    'quality_checks' => [
                        'full_body_visible' => [
                            'passed' => true,
                            'message' => 'Full-body framing is usable for Try-on L2.',
                        ],
                        'shoulders_detected' => [
                            'passed' => true,
                            'message' => 'Both shoulder keypoints are available.',
                        ],
                    ],
                    'keypoints' => [
                        ['name' => 'left_shoulder', 'x' => 410, 'y' => 390, 'confidence' => 0.70],
                        ['name' => 'right_shoulder', 'x' => 670, 'y' => 398, 'confidence' => 0.70],
                    ],
                    'pose_analysis' => [
                        'full_body_visible' => true,
                        'shoulder_balance' => 'balanced',
                        'pose_quality_score' => 0.86,
                        'pose_quality_status' => 'usable',
                        'improvement_tips' => [
                            'Use a straight full-body photo with both shoulders and hips visible.',
                        ],
                        'fit_notes' => [
                            'Image is usable for Try-on L2 preview and Magic Mirror analysis.',
                        ],
                    ],
                ]);
        });

        $response = $this->actingAs($user)->post(route('closet.tryon.store'), [
            'clothing_id' => $clothing->id,
            'person_photo' => UploadedFile::fake()->createWithContent(
                'person.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/l3vWJwAAAABJRU5ErkJggg==')
            ),
        ]);

        $response->assertRedirect(route('closet.tryon'));
        $response->assertSessionHas('status', '試穿 L1 姿態任務已完成，可人工驗收：姿態品質 usable / 86%。');

        $this->assertDatabaseHas('ai_jobs', [
            'user_id' => $user->id,
            'clothing_id' => $clothing->id,
            'job_type' => 'pose_analysis',
            'status' => 'degraded',
            'mode' => 'mock',
            'request_id' => 'pose-test-request',
        ]);

        $job = AiJob::where('job_type', 'pose_analysis')->latest()->first();

        $this->assertNotNull($job);
        $this->assertSame(0.86, $job->result_json['pose_quality_score']);
        $this->assertSame('usable', $job->result_json['pose_quality_status']);
        $this->assertTrue($job->result_json['quality_checks']['full_body_visible']['passed']);
        $this->assertSame(
            'Use a straight full-body photo with both shoulders and hips visible.',
            $job->result_json['pose_analysis']['improvement_tips'][0]
        );

        $page = $this->actingAs($user)->get(route('closet.tryon'));

        $page->assertOk();
        $page->assertSee('最新任務');
        $page->assertSee('最新任務可人工驗收');
        $page->assertSee('姿態品質');
        $page->assertSee('86%');
        $page->assertSee('品質檢查');
        $page->assertSee('改善建議');
    }

    public function test_tryon_l1_failed_ai_service_job_is_visible_as_latest_failure(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $clothing = Clothing::create([
            'user_id' => $user->id,
            'name' => 'Try-on Failed Jacket',
            'image_path' => 'clothes/tryon-failed-jacket.jpg',
            'image_url' => '/storage/clothes/tryon-failed-jacket.jpg',
            'category' => 'outerwear',
            'color' => 'black',
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $this->mock(AiService::class, function ($mock): void {
            $mock->shouldReceive('analyzePose')
                ->once()
                ->andReturn([
                    'schema_version' => 'v1',
                    'request_id' => 'pose-service-down-request',
                    'status' => 'failed',
                    'error' => [
                        'code' => 'AI_SERVICE_UNAVAILABLE',
                        'message' => '無法連線到 AI Service',
                    ],
                ]);
        });

        $response = $this->actingAs($user)->post(route('closet.tryon.store'), [
            'clothing_id' => $clothing->id,
            'person_photo' => UploadedFile::fake()->createWithContent(
                'person.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/l3vWJwAAAABJRU5ErkJggg==')
            ),
        ]);

        $response->assertRedirect(route('closet.tryon'));
        $response->assertSessionHas('status', '試穿 L1 任務失敗，已記錄錯誤。');

        $this->assertDatabaseHas('ai_jobs', [
            'user_id' => $user->id,
            'clothing_id' => $clothing->id,
            'job_type' => 'pose_analysis',
            'status' => 'failed',
            'request_id' => 'pose-service-down-request',
            'error_code' => 'AI_SERVICE_UNAVAILABLE',
            'error_message' => '無法連線到 AI Service',
        ]);

        $page = $this->actingAs($user)->get(route('closet.tryon'));

        $page->assertOk();
        $page->assertSee('最新任務');
        $page->assertSee('最新任務失敗');
        $page->assertSee('無法連線到 AI Service');
        $page->assertSee('AI_SERVICE_UNAVAILABLE');
    }

    public function test_authenticated_user_can_access_runway_video_workspace(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/workspace/runway-video');

        $response->assertStatus(200);
        $response->assertSee('伸展台影片');
    }

    public function test_authenticated_user_can_access_digital_twin_workspace(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/workspace/digital-twin');

        $response->assertStatus(200);
        $response->assertSee('數位分身');
    }

    public function test_guest_cannot_create_digital_twin_job(): void
    {
        $response = $this->post(route('workspace.digital-twin.store'), [
            'height_cm' => 170,
            'style_preference' => 'minimal',
            'common_occasion' => 'daily',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_analyze_digital_twin_closet(): void
    {
        $response = $this->post(route('workspace.digital-twin.analyze-closet'));

        $response->assertRedirect('/login');
    }

    public function test_runway_video_l2_creates_preview_job(): void
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
        $response->assertSessionHas('status', '伸展台影片 L2 預覽任務已建立，可人工驗收：預覽狀態 ready / 9:16。');

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
        $this->assertArrayHasKey('video_prompt', $job->result_json);
        $this->assertArrayHasKey('preview', $job->result_json);
        $this->assertArrayHasKey('provider', $job->result_json);
        $this->assertArrayHasKey('scene_timeline', $job->result_json);
        $this->assertArrayHasKey('scenes', $job->result_json);
        $this->assertSame('degraded_placeholder', $job->result_json['generation_status']);
        $this->assertSame('ready', $job->result_json['preview']['status']);
        $this->assertSame('9:16', $job->result_json['preview']['aspect_ratio']);
        $this->assertSame('veo', $job->result_json['provider']['target_provider']);
        $this->assertFalse($job->result_json['provider']['connected']);

        $page = $this->actingAs($user)->get(route('workspace.show', 'runway-video'));

        $page->assertOk();
        $page->assertSee('最新任務');
        $page->assertSee('最新伸展台任務可人工驗收');
        $page->assertSee('預覽狀態 ready');
        $page->assertSee('生成狀態');
        $page->assertSee('影片預覽');
        $page->assertSee('影片提示詞');
        $page->assertSee('degraded_placeholder');
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
        $response->assertSessionHas('status', '數位分身 L1 風格資料已建立，可人工驗收：fallback profile / degraded。');

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

        $page = $this->actingAs($user)->get(route('workspace.show', 'digital-twin'));

        $page->assertOk();
        $page->assertSee('最新任務');
        $page->assertSee('最新數位分身任務可人工驗收');
        $page->assertSee('分身預覽佔位');
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
        $response->assertSessionHas('status', '數位分身 L2 衣櫥風格分析已建立，可人工驗收：rule_based / degraded。');

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

        $page = $this->actingAs($user)->get(route('workspace.show', 'digital-twin'));

        $page->assertOk();
        $page->assertSee('最新任務');
        $page->assertSee('最新數位分身任務可人工驗收');
        $page->assertSee('衣櫥統計');
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
