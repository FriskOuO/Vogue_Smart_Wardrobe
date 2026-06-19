<?php

namespace Tests\Feature;

use App\Services\ProductionReadinessService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_public_policy_pages_are_available(): void
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('隱私政策')
            ->assertSee('AI 與第三方服務');

        $this->get('/terms')
            ->assertOk()
            ->assertSee('服務條款')
            ->assertSee('AI 產出限制');

        $this->get('/acceptable-use')
            ->assertOk()
            ->assertSee('使用限制')
            ->assertSee('禁止未授權影像');
    }

    public function test_production_readiness_flags_unsafe_production_config(): void
    {
        Config::set('app.env', 'production');
        Config::set('app.debug', true);
        Config::set('app.key', '');
        Config::set('app.url', 'http://localhost');
        Config::set('ai.mock_mode', true);
        Config::set('ai.gemini_api_key', '');
        Config::set('ai.internal_token', 'change_this_internal_ai_token');

        $summary = app(ProductionReadinessService::class)->summary([]);

        $this->assertFalse($summary['ok']);
        $this->assertGreaterThanOrEqual(2, $summary['failed']);
        $this->assertTrue(
            collect($summary['checks'])->contains(fn (array $check) => $check['name'] === 'Production environment'
                && $check['status'] === 'fail')
        );
        $this->assertTrue(
            collect($summary['checks'])->contains(fn (array $check) => $check['name'] === 'AI provider configuration'
                && $check['status'] === 'fail'
                && ! str_contains($check['message'], 'AIza'))
        );
    }

    public function test_production_readiness_blocks_secrets_and_model_artifacts(): void
    {
        $summary = app(ProductionReadinessService::class)->summary([
            ' M .env',
            '?? ai_service/models/clip/model.gguf',
        ]);

        $this->assertFalse($summary['ok']);
        $this->assertTrue(
            collect($summary['checks'])->contains(fn (array $check) => $check['name'] === 'Secret and model artifact safety'
                && $check['status'] === 'fail'
                && str_contains($check['message'], '.env')
                && str_contains($check['message'], 'model.gguf'))
        );
    }

    public function test_production_readiness_artisan_command_runs(): void
    {
        $this->artisan('vogueai:production-check')
            ->expectsOutput('VogueAI production readiness check')
            ->assertExitCode(0);
    }
}
