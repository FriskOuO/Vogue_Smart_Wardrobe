<?php

namespace Tests\Feature;

use App\Services\ExternalProviderSmokeService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalProviderSmokeTest extends TestCase
{
    public function test_external_provider_smoke_warns_when_calls_are_disabled(): void
    {
        Config::set('ai.external_provider_calls_enabled', false);

        $summary = app(ExternalProviderSmokeService::class)->summary();

        $this->assertTrue($summary['ok']);
        $this->assertSame(0, $summary['failed']);
        $this->assertSame(3, $summary['warnings']);
    }

    public function test_external_provider_smoke_passes_with_fake_ready_providers(): void
    {
        Config::set('ai.external_provider_calls_enabled', true);
        Config::set('ai.tryon_api_base_url', 'https://tryon.test');
        Config::set('ai.tryon_api_key', 'tryon-secret');
        Config::set('ai.video_provider', 'runway');
        Config::set('ai.runway_api_base_url', 'https://runway.test');
        Config::set('ai.runway_api_key', 'runway-secret');
        Config::set('ai.digital_twin_api_base_url', 'https://avatar.test');
        Config::set('ai.digital_twin_api_key', 'avatar-secret');

        Http::fake([
            'tryon.test/*' => Http::response([
                'id' => 'tryon-job-1',
                'output_url' => 'https://cdn.test/tryon.png',
            ]),
            'runway.test/*' => Http::response([
                'id' => 'video-job-1',
                'video_url' => 'https://cdn.test/video.mp4',
            ]),
            'avatar.test/*' => Http::response([
                'id' => 'avatar-job-1',
                'avatar_url' => 'https://cdn.test/avatar.png',
            ]),
        ]);

        $summary = app(ExternalProviderSmokeService::class)->summary();

        $this->assertTrue($summary['ok']);
        $this->assertSame(0, $summary['failed']);
        $this->assertSame(0, $summary['warnings']);
        $this->assertStringNotContainsString('tryon-secret', json_encode($summary, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('runway-secret', json_encode($summary, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('avatar-secret', json_encode($summary, JSON_THROW_ON_ERROR));
    }

    public function test_external_provider_smoke_can_run_only_tryon(): void
    {
        Config::set('ai.external_provider_calls_enabled', false);

        $summary = app(ExternalProviderSmokeService::class)->summary('tryon');

        $this->assertTrue($summary['ok']);
        $this->assertSame(0, $summary['failed']);
        $this->assertSame(1, $summary['warnings']);
        $this->assertSame('Try-on provider', $summary['checks'][0]['name']);
    }

    public function test_external_provider_smoke_accepts_fashn_processing_as_pass(): void
    {
        Config::set('ai.external_provider_calls_enabled', true);
        Config::set('ai.tryon_provider', 'fashn');
        Config::set('ai.tryon_model', 'tryon-max');
        Config::set('ai.tryon_api_base_url', 'https://api.fashn.test/v1');
        Config::set('ai.tryon_api_key', 'fashn-secret');
        Config::set('ai.tryon_create_endpoint', '/run');

        Http::fake([
            'api.fashn.test/v1/run' => Http::response([
                'id' => 'fashn-smoke-task-1',
                'error' => null,
            ]),
        ]);

        $summary = app(ExternalProviderSmokeService::class)->summary('tryon');

        $this->assertTrue($summary['ok']);
        $this->assertSame(0, $summary['failed']);
        $this->assertSame(0, $summary['warnings']);
        $this->assertSame('processing', $summary['checks'][0]['details']['status']);
        $this->assertStringNotContainsString('fashn-secret', json_encode($summary, JSON_THROW_ON_ERROR));
    }

    public function test_external_provider_smoke_fails_for_unknown_selector(): void
    {
        $summary = app(ExternalProviderSmokeService::class)->summary('unknown');

        $this->assertFalse($summary['ok']);
        $this->assertSame(1, $summary['failed']);
        $this->assertSame('External provider smoke selector', $summary['checks'][0]['name']);
    }

    public function test_external_provider_smoke_command_runs(): void
    {
        Config::set('ai.external_provider_calls_enabled', false);

        $this->artisan('vogueai:external-provider-smoke')
            ->expectsOutputToContain('VogueAI external provider smoke check')
            ->expectsOutputToContain('Summary:')
            ->assertExitCode(0);
    }

    public function test_external_provider_smoke_command_can_run_only_tryon(): void
    {
        Config::set('ai.external_provider_calls_enabled', false);

        $this->artisan('vogueai:external-provider-smoke --only=tryon')
            ->expectsOutputToContain('Try-on provider')
            ->doesntExpectOutputToContain('Runway / Veo provider')
            ->assertExitCode(0);
    }
}
