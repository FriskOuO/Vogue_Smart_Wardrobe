<?php

namespace Tests\Feature;

use App\Services\ProviderReadinessService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderReadinessTest extends TestCase
{
    public function test_provider_readiness_service_returns_summary(): void
    {
        Http::fake([
            '127.0.0.1:8001/health' => Http::response(['status' => 'ok'], 200),
            '127.0.0.1:8001/ai/vector-store/preflight*' => Http::response([
                'status' => 'ready',
            ], 200),
        ]);

        $summary = app(ProviderReadinessService::class)->summary();

        $this->assertArrayHasKey('ok', $summary);
        $this->assertArrayHasKey('failed', $summary);
        $this->assertArrayHasKey('warnings', $summary);
        $this->assertNotEmpty($summary['checks']);
        $this->assertTrue(
            collect($summary['checks'])->contains(fn (array $check) => $check['name'] === 'AI Search real mode entry'
                && $check['status'] === 'pass')
        );
        $this->assertTrue(
            collect($summary['checks'])->contains(fn (array $check) => $check['name'] === 'AI Stylist real mode entry'
                && $check['status'] === 'pass')
        );
    }

    public function test_provider_readiness_warns_when_external_services_are_not_running(): void
    {
        Http::fake([
            '127.0.0.1:8001/health' => Http::failedConnection(),
            '127.0.0.1:8001/ai/vector-store/preflight*' => Http::failedConnection(),
        ]);

        $summary = app(ProviderReadinessService::class)->summary();

        $this->assertTrue($summary['ok']);
        $this->assertGreaterThanOrEqual(1, $summary['warnings']);
        $this->assertTrue(
            collect($summary['checks'])->contains(fn (array $check) => $check['name'] === 'AI service health'
                && $check['status'] === 'warn')
        );
    }

    public function test_provider_readiness_artisan_command_runs(): void
    {
        Http::fake([
            '127.0.0.1:8001/health' => Http::response(['status' => 'ok'], 200),
            '127.0.0.1:8001/ai/vector-store/preflight*' => Http::response([
                'status' => 'ready',
            ], 200),
        ]);

        $this->artisan('vogueai:provider-check')
            ->expectsOutput('VogueAI provider readiness check')
            ->assertExitCode(0);
    }
}
