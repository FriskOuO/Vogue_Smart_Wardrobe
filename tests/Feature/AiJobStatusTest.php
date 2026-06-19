<?php

namespace Tests\Feature;

use App\Models\AiJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiJobStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_poll_owned_ai_job_status(): void
    {
        $user = User::factory()->create();
        $job = AiJob::create([
            'user_id' => $user->id,
            'job_type' => 'runway_video',
            'status' => 'degraded',
            'mode' => 'fallback',
            'request_id' => 'runway-status-test',
            'input_json' => ['prompt' => 'runway'],
            'result_json' => ['preview' => ['video_url' => null]],
            'retry_count' => 0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('ai-jobs.show', $job));

        $response->assertOk()
            ->assertJsonPath('id', $job->id)
            ->assertJsonPath('job_type', 'runway_video')
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('request_id', 'runway-status-test')
            ->assertJsonPath('retry_count', 0);
    }

    public function test_user_can_mark_owned_ai_job_for_retry(): void
    {
        $user = User::factory()->create();
        $job = AiJob::create([
            'user_id' => $user->id,
            'job_type' => 'pose_analysis',
            'status' => 'failed',
            'mode' => 'fallback',
            'request_id' => 'pose-retry-test',
            'input_json' => ['clothing_id' => 1],
            'result_json' => ['error' => ['code' => 'AI_SERVICE_UNAVAILABLE']],
            'error_code' => 'AI_SERVICE_UNAVAILABLE',
            'error_message' => 'AI service unavailable.',
            'retry_count' => 0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('ai-jobs.retry', $job));

        $response->assertRedirect(route('closet.tryon'));

        $job->refresh();

        $this->assertSame('pending_retry', $job->status);
        $this->assertSame(1, $job->retry_count);
        $this->assertNull($job->error_code);
        $this->assertNull($job->error_message);
        $this->assertNull($job->completed_at);
        $this->assertSame('failed', $job->result_json['retry']['previous_status']);
    }

    public function test_user_cannot_poll_another_users_ai_job(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $job = AiJob::create([
            'user_id' => $owner->id,
            'job_type' => 'digital_twin',
            'status' => 'degraded',
            'mode' => 'fallback',
            'request_id' => 'private-job-test',
        ]);

        $this->actingAs($otherUser)
            ->getJson(route('ai-jobs.show', $job))
            ->assertNotFound();
    }

    public function test_user_can_refresh_tryon_provider_status(): void
    {
        Config::set('ai.external_provider_calls_enabled', true);
        Config::set('ai.tryon_provider', 'huggingface_idm_vton');
        Config::set('ai.tryon_model', 'idm-vton');
        Config::set('ai.tryon_api_base_url', 'http://127.0.0.1:8001');
        Config::set('ai.tryon_status_endpoint', '/tryon/status/{id}');
        Config::set('ai.internal_token', 'test-internal-token');

        Http::fake([
            'http://127.0.0.1:8001/tryon/status/local_hf_tryon_abc123' => Http::response([
                'schema_version' => 'v1',
                'request_id' => 'pose-refresh-test',
                'status' => 'success',
                'mode' => 'huggingface_space',
                'provider' => 'huggingface_idm_vton',
                'provider_task_id' => 'local_hf_tryon_abc123',
                'output_url' => 'http://127.0.0.1:8001/static/tryon/local_hf_tryon_abc123.png',
            ]),
        ]);

        $user = User::factory()->create();
        $job = AiJob::create([
            'user_id' => $user->id,
            'job_type' => 'pose_analysis',
            'status' => 'processing',
            'mode' => 'huggingface_space',
            'request_id' => 'pose-refresh-test',
            'result_json' => [
                'tryon_provider_attempt' => [
                    'provider' => 'huggingface_idm_vton',
                    'status' => 'processing',
                    'provider_task_id' => 'local_hf_tryon_abc123',
                ],
            ],
            'started_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('ai-jobs.tryon-status', $job));

        $response->assertRedirect(route('closet.tryon'));

        $job->refresh();

        $this->assertSame('success', $job->status);
        $this->assertSame('huggingface_space', $job->mode);
        $this->assertNull($job->error_code);
        $this->assertSame(
            'http://127.0.0.1:8001/static/tryon/local_hf_tryon_abc123.png',
            $job->result_json['tryon_output_url'],
        );
        $this->assertSame('success', $job->result_json['tryon_provider_status']['status']);

        Http::assertSent(fn ($request) => $request->hasHeader('X-Internal-AI-Token', 'test-internal-token'));
    }
}
