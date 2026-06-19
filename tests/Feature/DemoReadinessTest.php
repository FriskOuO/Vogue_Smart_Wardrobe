<?php

namespace Tests\Feature;

use App\Services\DemoReadinessService;
use Tests\TestCase;

class DemoReadinessTest extends TestCase
{
    public function test_demo_readiness_service_returns_summary(): void
    {
        $summary = app(DemoReadinessService::class)->summary();

        $this->assertArrayHasKey('ok', $summary);
        $this->assertArrayHasKey('failed', $summary);
        $this->assertArrayHasKey('warnings', $summary);
        $this->assertNotEmpty($summary['checks']);
        $this->assertTrue(
            collect($summary['checks'])->contains(fn (array $check) => $check['name'] === 'AI mock mode')
        );
    }

    public function test_demo_readiness_artisan_command_runs(): void
    {
        $this->artisan('vogueai:demo-check')
            ->expectsOutput('VogueAI demo readiness check')
            ->assertExitCode(0);
    }
}
