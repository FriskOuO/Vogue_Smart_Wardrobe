<?php

namespace Tests\Feature;

use App\Services\ModelProviderMatrixService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ModelProviderMatrixTest extends TestCase
{
    public function test_model_provider_matrix_lists_all_target_capabilities(): void
    {
        $summary = app(ModelProviderMatrixService::class)->summary();
        $capabilities = collect($summary['providers'])->pluck('capability');

        $this->assertTrue($summary['ok']);
        $this->assertSame(13, $capabilities->count());
        $this->assertTrue($capabilities->contains('Gemini: 穿搭顧問'));
        $this->assertTrue($capabilities->contains('Gemini: 聊天助理'));
        $this->assertTrue($capabilities->contains('Gemini: 文字理解'));
        $this->assertTrue($capabilities->contains('CLIP: 文字搜尋'));
        $this->assertTrue($capabilities->contains('CLIP: 圖片向量'));
        $this->assertTrue($capabilities->contains('BLIP: 衣物描述'));
        $this->assertTrue($capabilities->contains('BLIP VQA: 進階衣物理解'));
        $this->assertTrue($capabilities->contains('多輸出分類: 衣物自動標籤'));
        $this->assertTrue($capabilities->contains('YOLO Pose: 姿態檢查'));
        $this->assertTrue($capabilities->contains('Qdrant: 正式向量資料庫'));
        $this->assertTrue($capabilities->contains('Try-on: 真實換裝模型'));
        $this->assertTrue($capabilities->contains('Runway / Veo: 真實影片生成'));
        $this->assertTrue($capabilities->contains('Digital Twin: 3D / 多視角 / avatar provider'));
    }

    public function test_model_provider_matrix_does_not_print_secret_values(): void
    {
        Config::set('ai.gemini_api_key', 'AIza-secret-test-key');
        Config::set('ai.tryon_api_key', 'tryon-secret-test-key');
        Config::set('ai.veo_api_key', 'veo-secret-test-key');
        Config::set('ai.digital_twin_api_key', 'avatar-secret-test-key');

        $summary = app(ModelProviderMatrixService::class)->summary();
        $encoded = json_encode($summary, JSON_UNESCAPED_UNICODE);

        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('AIza-secret-test-key', $encoded);
        $this->assertStringNotContainsString('tryon-secret-test-key', $encoded);
        $this->assertStringNotContainsString('veo-secret-test-key', $encoded);
        $this->assertStringNotContainsString('avatar-secret-test-key', $encoded);
    }

    public function test_provider_matrix_artisan_command_runs(): void
    {
        $this->artisan('vogueai:provider-matrix')
            ->expectsOutput('VogueAI model provider matrix')
            ->expectsOutput('Secrets: API key values are intentionally never printed.')
            ->assertExitCode(0);
    }
}
