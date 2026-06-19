<?php

namespace Tests\Feature;

use App\Services\RealModeAcceptanceService;
use Tests\TestCase;

class RealModeAcceptanceTest extends TestCase
{
    public function test_real_mode_acceptance_service_returns_summary(): void
    {
        $summary = app(RealModeAcceptanceService::class)->summary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('ok', $summary);
        $this->assertArrayHasKey('checks', $summary);
        $this->assertTrue(
            collect($summary['checks'])->contains(
                fn (array $check) => $check['name'] === 'AI Search real mode wiring'
                    && $check['status'] === 'pass'
            )
        );
        $this->assertTrue(
            collect($summary['checks'])->contains(
                fn (array $check) => $check['name'] === 'AI Stylist missing-key fallback coverage'
                    && $check['status'] === 'pass'
            )
        );
    }

    public function test_real_mode_acceptance_artisan_command_runs(): void
    {
        $this->artisan('vogueai:real-mode-check')
            ->expectsOutput('VogueAI real mode acceptance check')
            ->assertExitCode(0);
    }
}
