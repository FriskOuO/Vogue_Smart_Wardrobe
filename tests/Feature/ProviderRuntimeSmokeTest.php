<?php

namespace Tests\Feature;

use App\Services\ProviderRuntimeSmokeService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderRuntimeSmokeTest extends TestCase
{
    public function test_provider_runtime_smoke_passes_when_all_ai_service_providers_are_ready(): void
    {
        Config::set('ai.service_url', 'http://ai-service.test');
        Config::set('ai.internal_token', 'internal-test-token');

        Http::fake([
            'ai-service.test/health' => Http::response([
                'status' => 'ok',
                'dependencies' => [
                    'clip' => 'available',
                    'blip' => 'available',
                    'qdrant' => 'available',
                ],
            ]),
            'ai-service.test/ai/embed/text' => Http::response([
                'status' => 'ready',
                'mode' => 'real_adapter',
            ]),
            'ai-service.test/ai/embed/image' => Http::response([
                'status' => 'ready',
                'mode' => 'real_adapter',
            ]),
            'ai-service.test/ai/attributes' => Http::response([
                'status' => 'ready',
                'mode' => 'hybrid',
                'real_adapter_attempt' => [
                    'status' => 'ready',
                    'mode' => 'real_adapter',
                ],
            ]),
            'ai-service.test/ai/vector-store/preflight*' => Http::response([
                'status' => 'ready',
                'readiness' => [
                    'can_attempt_connection' => true,
                    'connected' => true,
                    'collection_exists' => true,
                ],
            ]),
        ]);

        $summary = app(ProviderRuntimeSmokeService::class)->summary(connectQdrant: true);

        $this->assertTrue($summary['ok']);
        $this->assertSame(0, $summary['failed']);
        $this->assertSame(0, $summary['warnings']);

        Http::assertSent(fn ($request) => $request->hasHeader('X-Internal-AI-Token', 'internal-test-token')
            && str_contains($request->url(), '/ai/embed/text'));
    }

    public function test_provider_runtime_smoke_warns_when_model_runtime_is_degraded(): void
    {
        Config::set('ai.service_url', 'http://ai-service.test');

        Http::fake([
            'ai-service.test/health' => Http::response(['status' => 'ok']),
            'ai-service.test/ai/embed/text' => Http::response([
                'status' => 'degraded',
                'error_code' => 'CLIP_DEPENDENCIES_NOT_INSTALLED',
            ]),
            'ai-service.test/ai/embed/image' => Http::response([
                'status' => 'degraded',
                'error_code' => 'CLIP_DEPENDENCIES_NOT_INSTALLED',
            ]),
            'ai-service.test/ai/attributes' => Http::response([
                'status' => 'degraded',
                'real_adapter_attempt' => [
                    'status' => 'degraded',
                    'error_code' => 'BLIP_DEPENDENCIES_NOT_INSTALLED',
                ],
            ]),
            'ai-service.test/ai/vector-store/preflight*' => Http::response([
                'status' => 'degraded',
                'readiness' => [
                    'can_attempt_connection' => false,
                ],
            ]),
        ]);

        $summary = app(ProviderRuntimeSmokeService::class)->summary();

        $this->assertTrue($summary['ok']);
        $this->assertSame(0, $summary['failed']);
        $this->assertSame(4, $summary['warnings']);
    }

    public function test_provider_runtime_smoke_command_runs(): void
    {
        Config::set('ai.service_url', 'http://ai-service.test');

        Http::fake([
            'ai-service.test/health' => Http::response(['status' => 'ok']),
            'ai-service.test/*' => Http::response(['status' => 'degraded']),
        ]);

        $this->artisan('vogueai:provider-runtime-smoke')
            ->expectsOutputToContain('VogueAI provider runtime smoke check')
            ->expectsOutputToContain('Summary:')
            ->assertExitCode(0);
    }
}
