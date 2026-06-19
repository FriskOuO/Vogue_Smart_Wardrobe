<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ProviderRuntimeSmokeService
{
    /**
     * @return array{ok: bool, failed: int, warnings: int, checks: array<int, array<string, mixed>>}
     */
    public function summary(bool $connectQdrant = false): array
    {
        $checks = [
            $this->healthCheck(),
            $this->clipTextSmoke(),
            $this->clipImageSmoke(),
            $this->blipCaptionSmoke(),
            $this->qdrantPreflight($connectQdrant),
        ];

        $failed = collect($checks)->where('status', 'fail')->count();
        $warnings = collect($checks)->where('status', 'warn')->count();

        return [
            'ok' => $failed === 0,
            'failed' => $failed,
            'warnings' => $warnings,
            'checks' => $checks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function healthCheck(): array
    {
        $response = $this->get('/health');

        if (($response['http_ok'] ?? false) && data_get($response, 'json.status') === 'ok') {
            return $this->check('AI Service health', 'pass', 'AI Service health endpoint is reachable.', $response['json']);
        }

        return $this->check('AI Service health', 'fail', 'AI Service health endpoint is not reachable.', $response);
    }

    /**
     * @return array<string, mixed>
     */
    private function clipTextSmoke(): array
    {
        $response = $this->post('/ai/embed/text', [
            'schema_version' => 'v1',
            'request_id' => 'provider_runtime_clip_text_' . now()->format('YmdHis'),
            'user_id' => 0,
            'query' => 'white shirt',
            'locale' => 'zh_TW',
            'model' => 'clip-vit-base-patch32',
            'mock_mode' => false,
        ]);

        $status = (string) data_get($response, 'json.status', 'failed');

        return $this->check(
            name: 'CLIP text embedding',
            status: $status === 'ready' ? 'pass' : 'warn',
            message: $status === 'ready'
                ? 'CLIP text embedding returned ready / real_adapter.'
                : 'CLIP text embedding adapter exists but runtime is degraded or unavailable.',
            details: $response['json'] ?? $response,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function clipImageSmoke(): array
    {
        $response = $this->post('/ai/embed/image', [
            'schema_version' => 'v1',
            'request_id' => 'provider_runtime_clip_image_' . now()->format('YmdHis'),
            'user_id' => 0,
            'clothing_id' => 0,
            'image_path' => 'public/images/demo/white-shirt.jpg',
            'image_url' => asset('images/demo/white-shirt.jpg'),
            'model' => 'clip-vit-base-patch32',
            'store_to_vector_db' => false,
            'mock_mode' => false,
        ]);

        $status = (string) data_get($response, 'json.status', 'failed');

        return $this->check(
            name: 'CLIP image embedding',
            status: $status === 'ready' ? 'pass' : 'warn',
            message: $status === 'ready'
                ? 'CLIP image embedding returned ready / real_adapter.'
                : 'CLIP image embedding adapter exists but runtime is degraded or unavailable.',
            details: $response['json'] ?? $response,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function blipCaptionSmoke(): array
    {
        $response = $this->post('/ai/attributes', [
            'schema_version' => 'v1',
            'request_id' => 'provider_runtime_blip_' . now()->format('YmdHis'),
            'user_id' => 0,
            'clothing_id' => 0,
            'image_path' => 'public/images/demo/white-shirt.jpg',
            'image_url' => asset('images/demo/white-shirt.jpg'),
            'locale' => 'zh_TW',
            'mock_mode' => false,
        ]);

        $attemptStatus = (string) data_get($response, 'json.real_adapter_attempt.status', data_get($response, 'json.status', 'failed'));

        return $this->check(
            name: 'BLIP clothing description',
            status: $attemptStatus === 'ready' ? 'pass' : 'warn',
            message: $attemptStatus === 'ready'
                ? 'BLIP clothing caption returned ready / real_adapter.'
                : 'BLIP caption adapter exists but runtime is degraded or unavailable.',
            details: $response['json'] ?? $response,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function qdrantPreflight(bool $connectQdrant): array
    {
        $response = $this->get('/ai/vector-store/preflight?check_connection=' . ($connectQdrant ? 'true' : 'false'), true);
        $ready = data_get($response, 'json.status') === 'ready';
        $canAttempt = (bool) data_get($response, 'json.readiness.can_attempt_connection', false);

        return $this->check(
            name: 'Qdrant vector store',
            status: $ready ? 'pass' : 'warn',
            message: match (true) {
                $ready => 'Qdrant connection and collection are ready.',
                $canAttempt && ! $connectQdrant => 'Qdrant client is available; rerun with --connect-qdrant for daemon/collection verification.',
                default => 'Qdrant contract exists but runtime is degraded or not connected.',
            },
            details: $response['json'] ?? $response,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $endpoint, bool $internal = false): array
    {
        try {
            $request = Http::timeout((int) config('ai.timeout_seconds', 30))
                ->acceptJson();

            if ($internal) {
                $request = $request->withHeaders([
                    'X-Internal-AI-Token' => (string) config('ai.internal_token'),
                ]);
            }

            $response = $request->get($this->url($endpoint));

            return [
                'http_ok' => $response->successful(),
                'http_status' => $response->status(),
                'json' => $response->json(),
            ];
        } catch (\Throwable $exception) {
            return [
                'http_ok' => false,
                'error_code' => 'AI_SERVICE_CLIENT_ERROR',
                'error_message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function post(string $endpoint, array $payload): array
    {
        try {
            $response = Http::timeout((int) config('ai.timeout_seconds', 30))
                ->withHeaders([
                    'X-Internal-AI-Token' => (string) config('ai.internal_token'),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->url($endpoint), $payload);

            return [
                'http_ok' => $response->successful(),
                'http_status' => $response->status(),
                'json' => $response->json(),
            ];
        } catch (\Throwable $exception) {
            return [
                'http_ok' => false,
                'error_code' => 'AI_SERVICE_CLIENT_ERROR',
                'error_message' => $exception->getMessage(),
            ];
        }
    }

    private function url(string $endpoint): string
    {
        return rtrim((string) config('ai.service_url'), '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    private function check(string $name, string $status, string $message, array $details = []): array
    {
        return [
            'name' => $name,
            'status' => $status,
            'message' => $message,
            'details' => $details,
        ];
    }
}
