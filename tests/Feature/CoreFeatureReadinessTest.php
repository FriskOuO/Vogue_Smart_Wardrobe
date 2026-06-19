<?php

namespace Tests\Feature;

use App\Services\CoreFeatureReadinessService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CoreFeatureReadinessTest extends TestCase
{
    public function test_core_feature_readiness_lists_all_formalized_core_features(): void
    {
        $summary = app(CoreFeatureReadinessService::class)->summary();

        $this->assertTrue($summary['ok']);
        $this->assertSame(0, $summary['failed']);

        $featureNames = collect($summary['features'])->pluck('name')->all();

        $this->assertContains('My Closet', $featureNames);
        $this->assertContains('AI Search', $featureNames);
        $this->assertContains('AI Stylist', $featureNames);
        $this->assertContains('Try-on', $featureNames);
        $this->assertContains('Runway Video', $featureNames);
        $this->assertContains('Digital Twin', $featureNames);
    }

    public function test_core_feature_gate_includes_job_polling_and_retry_routes(): void
    {
        $this->assertTrue(Route::has('ai-jobs.show'));
        $this->assertTrue(Route::has('ai-jobs.retry'));

        $summary = app(CoreFeatureReadinessService::class)->summary();
        $tryOn = collect($summary['features'])->firstWhere('name', 'Try-on');

        $signalNames = collect($tryOn['signals'])->pluck('name')->all();

        $this->assertContains('job status polling route', $signalNames);
        $this->assertContains('job retry route', $signalNames);
    }

    public function test_core_feature_check_command_runs(): void
    {
        $this->artisan('vogueai:core-feature-check')
            ->expectsOutputToContain('VogueAI core feature readiness check')
            ->expectsOutputToContain('Summary:')
            ->assertExitCode(0);
    }
}
