<?php

namespace Tests\Feature;

use App\Services\ExternalModelProviderService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalModelProviderTest extends TestCase
{
    public function test_external_provider_calls_are_disabled_by_default(): void
    {
        Config::set('ai.external_provider_calls_enabled', false);
        Config::set('ai.tryon_api_base_url', 'https://tryon.test');
        Config::set('ai.tryon_api_key', 'tryon-secret-test-key');

        Http::fake();

        $result = app(ExternalModelProviderService::class)->generateTryOn([
            'request_id' => 'tryon-test',
        ]);

        $this->assertSame('degraded', $result['status']);
        $this->assertSame('fallback', $result['mode']);
        $this->assertTrue($result['fallback_active']);
        $this->assertSame('EXTERNAL_PROVIDER_CALLS_DISABLED', $result['error_code']);

        Http::assertNothingSent();
    }

    public function test_configured_video_provider_sends_authorization_without_returning_secret(): void
    {
        Config::set('ai.external_provider_calls_enabled', true);
        Config::set('ai.video_provider', 'runway');
        Config::set('ai.video_model', 'runway-gen4');
        Config::set('ai.runway_api_base_url', 'https://runway.test');
        Config::set('ai.runway_api_key', 'video-secret-test-key');

        Http::fake([
            'runway.test/*' => Http::response([
                'id' => 'video-job-1',
                'video_url' => 'https://cdn.test/video.mp4',
            ]),
        ]);

        $result = app(ExternalModelProviderService::class)->generateVideo([
            'request_id' => 'video-test',
            'prompt' => 'clean runway video',
        ]);

        $this->assertSame('ready', $result['status']);
        $this->assertSame('real_adapter', $result['mode']);
        $this->assertFalse($result['fallback_active']);
        $this->assertSame('video-job-1', $result['provider_job_id']);
        $this->assertSame('https://cdn.test/video.mp4', $result['output_url']);
        $this->assertStringNotContainsString('video-secret-test-key', json_encode($result, JSON_THROW_ON_ERROR));

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer video-secret-test-key')
            && str_contains($request->url(), 'https://runway.test/v1/image_to_video'));
    }

    public function test_digital_twin_missing_provider_config_degrades_safely(): void
    {
        Config::set('ai.external_provider_calls_enabled', true);
        Config::set('ai.digital_twin_api_base_url', null);
        Config::set('ai.digital_twin_api_key', null);

        Http::fake();

        $result = app(ExternalModelProviderService::class)->generateDigitalTwin([
            'request_id' => 'avatar-test',
        ]);

        $this->assertSame('degraded', $result['status']);
        $this->assertSame('DIGITAL_TWIN_PROVIDER_NOT_CONFIGURED', $result['error_code']);
        $this->assertTrue($result['fallback_active']);

        Http::assertNothingSent();
    }

    public function test_fashn_tryon_create_maps_payload_and_keeps_secret_out_of_result(): void
    {
        Config::set('ai.external_provider_calls_enabled', true);
        Config::set('ai.tryon_provider', 'fashn');
        Config::set('ai.tryon_model', 'tryon-max');
        Config::set('ai.tryon_api_base_url', 'https://api.fashn.test/v1');
        Config::set('ai.tryon_api_key', 'fashn-secret-test-key');
        Config::set('ai.tryon_create_endpoint', '/run');
        Config::set('ai.tryon_resolution', '1k');
        Config::set('ai.tryon_generation_mode', 'balanced');
        Config::set('ai.tryon_output_format', 'png');
        Config::set('ai.tryon_return_base64', false);

        Http::fake([
            'api.fashn.test/v1/run' => Http::response([
                'id' => 'fashn-task-1',
                'error' => null,
            ]),
        ]);

        $result = app(ExternalModelProviderService::class)->generateTryOn([
            'request_id' => 'local-request-1',
            'user_id' => 7,
            'person_image_url' => 'https://cdn.test/person.jpg',
            'clothing_image_url' => 'https://cdn.test/clothing.jpg',
            'pose_analysis' => ['pose_quality_status' => 'pass'],
        ]);

        $this->assertSame('processing', $result['status']);
        $this->assertSame('external', $result['mode']);
        $this->assertFalse($result['fallback_active']);
        $this->assertSame('fashn', $result['provider']);
        $this->assertSame('tryon-max', $result['model']);
        $this->assertSame('fashn-task-1', $result['provider_task_id']);
        $this->assertNull($result['output_url']);
        $this->assertStringNotContainsString('fashn-secret-test-key', json_encode($result, JSON_THROW_ON_ERROR));

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->hasHeader('Authorization', 'Bearer fashn-secret-test-key')
                && $request->url() === 'https://api.fashn.test/v1/run'
                && $body['model_name'] === 'tryon-max'
                && $body['inputs']['model_image'] === 'https://cdn.test/person.jpg'
                && $body['inputs']['product_image'] === 'https://cdn.test/clothing.jpg'
                && $body['inputs']['resolution'] === '1k'
                && $body['inputs']['generation_mode'] === 'balanced'
                && $body['inputs']['num_images'] === 1
                && $body['inputs']['output_format'] === 'png'
                && $body['inputs']['return_base64'] === false
                && ! array_key_exists('request_id', $body)
                && ! array_key_exists('user_id', $body)
                && ! array_key_exists('pose_analysis', $body);
        });
    }

    public function test_huggingface_idm_vton_tryon_preserves_ai_service_processing_status(): void
    {
        Config::set('ai.external_provider_calls_enabled', true);
        Config::set('ai.tryon_provider', 'huggingface_idm_vton');
        Config::set('ai.tryon_model', 'idm-vton');
        Config::set('ai.tryon_api_base_url', 'http://127.0.0.1:8001');
        Config::set('ai.tryon_api_key', '');
        Config::set('ai.tryon_create_endpoint', '/tryon/generate');

        Http::fake([
            'http://127.0.0.1:8001/tryon/generate' => Http::response([
                'schema_version' => 'v1',
                'request_id' => 'tryon-test',
                'status' => 'processing',
                'mode' => 'huggingface_space',
                'provider' => 'huggingface_idm_vton',
                'provider_task_id' => 'local_hf_tryon_abc123',
                'output_url' => null,
            ]),
        ]);

        $result = app(ExternalModelProviderService::class)->generateTryOn([
            'request_id' => 'tryon-test',
            'user_id' => 1,
            'person_image_url' => 'https://example.com/person.jpg',
            'clothing_image_url' => 'https://example.com/clothing.jpg',
            'pose_analysis' => [],
        ]);

        $this->assertSame('processing', $result['status']);
        $this->assertSame('huggingface_space', $result['mode']);
        $this->assertFalse($result['fallback_active']);
        $this->assertSame('local_hf_tryon_abc123', $result['provider_task_id']);
        $this->assertSame('local_hf_tryon_abc123', $result['provider_job_id']);
        $this->assertNull($result['output_url']);
    }

    public function test_fashn_tryon_poll_completed_result_maps_output_url(): void
    {
        Config::set('ai.external_provider_calls_enabled', true);
        Config::set('ai.tryon_provider', 'fashn');
        Config::set('ai.tryon_model', 'tryon-max');
        Config::set('ai.tryon_api_base_url', 'https://api.fashn.test/v1');
        Config::set('ai.tryon_api_key', 'fashn-secret-test-key');
        Config::set('ai.tryon_status_endpoint', '/status/{id}');

        Http::fake([
            'api.fashn.test/v1/status/fashn-task-1' => Http::response([
                'id' => 'fashn-task-1',
                'status' => 'completed',
                'output' => ['https://cdn.fashn.test/fashn-task-1/output_0.png'],
                'error' => null,
            ]),
        ]);

        $result = app(ExternalModelProviderService::class)->pollTryOn('fashn-task-1', 'local-request-1');

        $this->assertSame('success', $result['status']);
        $this->assertSame('external', $result['mode']);
        $this->assertFalse($result['fallback_active']);
        $this->assertSame('completed', $result['external_status']);
        $this->assertSame('fashn-task-1', $result['provider_task_id']);
        $this->assertSame('https://cdn.fashn.test/fashn-task-1/output_0.png', $result['output_url']);
        $this->assertNull($result['error_code']);
    }

    public function test_fashn_tryon_poll_failed_result_maps_error_details(): void
    {
        Config::set('ai.external_provider_calls_enabled', true);
        Config::set('ai.tryon_provider', 'fashn');
        Config::set('ai.tryon_model', 'tryon-max');
        Config::set('ai.tryon_api_base_url', 'https://api.fashn.test/v1');
        Config::set('ai.tryon_api_key', 'fashn-secret-test-key');
        Config::set('ai.tryon_status_endpoint', '/status/{id}');

        Http::fake([
            'api.fashn.test/v1/status/fashn-task-1' => Http::response([
                'id' => 'fashn-task-1',
                'status' => 'failed',
                'error' => [
                    'name' => 'ImageLoadError',
                    'message' => 'Error loading image or invalid image URL',
                ],
            ]),
        ]);

        $result = app(ExternalModelProviderService::class)->pollTryOn('fashn-task-1', 'local-request-1');

        $this->assertSame('failed', $result['status']);
        $this->assertSame('external', $result['mode']);
        $this->assertTrue($result['fallback_active']);
        $this->assertSame('failed', $result['external_status']);
        $this->assertSame('ImageLoadError', $result['error_code']);
        $this->assertSame('Error loading image or invalid image URL', $result['error_message']);
        $this->assertNull($result['output_url']);
    }

    public function test_tryon_status_command_polls_once_without_printing_secret(): void
    {
        Config::set('ai.external_provider_calls_enabled', true);
        Config::set('ai.tryon_provider', 'fashn');
        Config::set('ai.tryon_model', 'tryon-max');
        Config::set('ai.tryon_api_base_url', 'https://api.fashn.test/v1');
        Config::set('ai.tryon_api_key', 'fashn-secret-test-key');
        Config::set('ai.tryon_status_endpoint', '/status/{id}');

        Http::fake([
            'api.fashn.test/v1/status/fashn-task-1' => Http::response([
                'id' => 'fashn-task-1',
                'status' => 'completed',
                'output' => ['https://cdn.fashn.test/fashn-task-1/output_0.png'],
                'error' => null,
            ]),
        ]);

        $this->artisan('vogueai:tryon-status fashn-task-1 --request-id=local-request-1')
            ->expectsOutputToContain('VogueAI try-on provider status')
            ->expectsOutputToContain('"status": "success"')
            ->expectsOutputToContain('https://cdn.fashn.test/fashn-task-1/output_0.png')
            ->doesntExpectOutputToContain('fashn-secret-test-key')
            ->assertExitCode(0);
    }
}
