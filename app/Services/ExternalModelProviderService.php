<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ExternalModelProviderService
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function generateTryOn(array $payload): array
    {
        $provider = (string) config('ai.tryon_provider', 'external_tryon');

        if ($provider === 'fashn') {
            return $this->generateFashnTryOn($payload);
        }

        return $this->postProvider(
            capability: 'try_on',
            provider: $provider,
            model: (string) config('ai.tryon_model', 'virtual-tryon-v1'),
            baseUrl: config('ai.tryon_api_base_url'),
            apiKey: config('ai.tryon_api_key'),
            endpoint: (string) config('ai.tryon_create_endpoint', '/tryon/generate'),
            payload: $payload,
            missingCode: 'TRYON_PROVIDER_NOT_CONFIGURED',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function pollTryOn(string $providerTaskId, ?string $requestId = null): array
    {
        $provider = (string) config('ai.tryon_provider', 'external_tryon');

        if ($provider === 'fashn') {
            return $this->pollFashnTryOn($providerTaskId, $requestId);
        }

        return $this->pollGenericTryOn($provider, $providerTaskId, $requestId);
    }

    /**
     * @return array<string, mixed>
     */
    private function pollGenericTryOn(string $provider, string $providerTaskId, ?string $requestId): array
    {
        $model = (string) config('ai.tryon_model', 'virtual-tryon-v1');
        $endpoint = str_replace('{id}', rawurlencode($providerTaskId), (string) config('ai.tryon_status_endpoint', '/tryon/status/{id}'));
        $baseUrl = rtrim((string) config('ai.tryon_api_base_url'), '/');
        $isInternalAiService = $provider === 'huggingface_idm_vton';
        $apiKey = $isInternalAiService
            ? (string) config('ai.internal_token')
            : (string) config('ai.tryon_api_key');

        $contract = [
            'schema_version' => 'v1',
            'capability' => 'try_on',
            'provider' => $provider,
            'model' => $model,
            'endpoint' => $endpoint,
            'provider_task_id' => $providerTaskId,
            'provider_job_id' => $providerTaskId,
            'request_id' => $requestId,
            'api_key_configured' => $apiKey !== '',
            'external_provider_calls_enabled' => (bool) config('ai.external_provider_calls_enabled', false),
        ];

        if (! (bool) config('ai.external_provider_calls_enabled', false)) {
            return [
                ...$contract,
                'status' => 'degraded',
                'mode' => 'fallback',
                'fallback_active' => true,
                'output_url' => null,
                'error_code' => 'EXTERNAL_PROVIDER_CALLS_DISABLED',
                'error_message' => 'External provider calls are disabled. Set AI_EXTERNAL_PROVIDER_CALLS=true after manual provider verification.',
            ];
        }

        if ($baseUrl === '' || $apiKey === '') {
            return [
                ...$contract,
                'status' => 'degraded',
                'mode' => 'fallback',
                'fallback_active' => true,
                'output_url' => null,
                'error_code' => 'TRYON_PROVIDER_NOT_CONFIGURED',
                'error_message' => 'External provider base URL or API key is not configured.',
            ];
        }

        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];
            if ($isInternalAiService) {
                $headers['X-Internal-AI-Token'] = $apiKey;
            } else {
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }

            $response = Http::timeout((int) config('ai.timeout_seconds', 30))
                ->withHeaders($headers)
                ->get($baseUrl . $endpoint);

            if (! $response->successful()) {
                return [
                    ...$contract,
                    'status' => 'degraded',
                    'mode' => 'real_adapter_attempt',
                    'fallback_active' => true,
                    'output_url' => null,
                    'http_status' => $response->status(),
                    'error_code' => 'TRY_ON_STATUS_HTTP_ERROR',
                    'error_message' => 'External provider returned HTTP ' . $response->status() . ' while checking task status.',
                ];
            }

            $body = $response->json() ?? [];
            $body = is_array($body) ? $body : [];
            $status = (string) ($body['status'] ?? 'degraded');

            return [
                ...$contract,
                'status' => $status,
                'mode' => $body['mode'] ?? 'real_adapter',
                'external_status' => $body['external_status'] ?? $status,
                'fallback_active' => in_array($status, ['degraded', 'failed'], true),
                'output_url' => $body['output_url'] ?? null,
                'raw_response' => $body,
                'error_code' => $body['error_code'] ?? null,
                'error_message' => $body['error_message'] ?? $body['message'] ?? null,
            ];
        } catch (\Throwable $exception) {
            return [
                ...$contract,
                'status' => 'degraded',
                'mode' => 'real_adapter_attempt',
                'fallback_active' => true,
                'output_url' => null,
                'error_code' => 'TRY_ON_STATUS_CLIENT_ERROR',
                'error_message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function generateVideo(array $payload): array
    {
        $provider = (string) config('ai.video_provider', 'veo');
        $baseUrl = $provider === 'runway'
            ? config('ai.runway_api_base_url')
            : config('ai.veo_api_base_url');
        $apiKey = $provider === 'runway'
            ? config('ai.runway_api_key')
            : config('ai.veo_api_key');
        $endpoint = $provider === 'runway'
            ? '/v1/image_to_video'
            : '/v1beta/models/' . config('ai.video_model', 'veo-3') . ':generateVideo';

        return $this->postProvider(
            capability: 'video_generation',
            provider: $provider,
            model: (string) config('ai.video_model', 'veo-3'),
            baseUrl: $baseUrl,
            apiKey: $apiKey,
            endpoint: $endpoint,
            payload: $payload,
            missingCode: 'VIDEO_PROVIDER_NOT_CONFIGURED',
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function generateDigitalTwin(array $payload): array
    {
        return $this->postProvider(
            capability: 'digital_twin_avatar',
            provider: (string) config('ai.digital_twin_provider', 'avatar_3d'),
            model: (string) config('ai.digital_twin_model', 'avatar-3d-multiview-v1'),
            baseUrl: config('ai.digital_twin_api_base_url'),
            apiKey: config('ai.digital_twin_api_key'),
            endpoint: '/avatar/generate',
            payload: $payload,
            missingCode: 'DIGITAL_TWIN_PROVIDER_NOT_CONFIGURED',
        );
    }

    /**
     * @param mixed $baseUrl
     * @param mixed $apiKey
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function postProvider(
        string $capability,
        string $provider,
        string $model,
        mixed $baseUrl,
        mixed $apiKey,
        string $endpoint,
        array $payload,
        string $missingCode,
    ): array {
        $baseUrl = rtrim((string) $baseUrl, '/');
        $isInternalAiService = $provider === 'huggingface_idm_vton';
        $apiKey = $isInternalAiService
            ? (string) config('ai.internal_token')
            : (string) $apiKey;

        $contract = [
            'schema_version' => 'v1',
            'capability' => $capability,
            'provider' => $provider,
            'model' => $model,
            'endpoint' => $endpoint,
            'api_key_configured' => $apiKey !== '',
            'external_provider_calls_enabled' => (bool) config('ai.external_provider_calls_enabled', false),
        ];

        if (! (bool) config('ai.external_provider_calls_enabled', false)) {
            return [
                ...$contract,
                'status' => 'degraded',
                'mode' => 'fallback',
                'fallback_active' => true,
                'error_code' => 'EXTERNAL_PROVIDER_CALLS_DISABLED',
                'error_message' => 'External provider calls are disabled. Set AI_EXTERNAL_PROVIDER_CALLS=true after manual provider verification.',
            ];
        }

        if ($baseUrl === '' || $apiKey === '') {
            return [
                ...$contract,
                'status' => 'degraded',
                'mode' => 'fallback',
                'fallback_active' => true,
                'error_code' => $missingCode,
                'error_message' => 'External provider base URL or API key is not configured.',
            ];
        }

        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];
            if ($isInternalAiService) {
                $headers['X-Internal-AI-Token'] = $apiKey;
            } else {
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }

            $response = Http::timeout((int) config('ai.timeout_seconds', 30))
                ->withHeaders($headers)
                ->post($baseUrl . $endpoint, [
                    'model' => $model,
                    ...$payload,
                ]);

            if (! $response->successful()) {
                return [
                    ...$contract,
                    'status' => 'degraded',
                    'mode' => 'real_adapter_attempt',
                    'fallback_active' => true,
                    'http_status' => $response->status(),
                    'error_code' => strtoupper($capability) . '_HTTP_ERROR',
                    'error_message' => 'External provider returned HTTP ' . $response->status() . '.',
                ];
            }

            $body = $response->json() ?? [];
            $body = is_array($body) ? $body : [];
            $providerJobId = $body['provider_task_id'] ?? $body['id'] ?? $body['job_id'] ?? null;

            return [
                ...$contract,
                'status' => $body['status'] ?? 'ready',
                'mode' => $body['mode'] ?? 'real_adapter',
                'fallback_active' => in_array(($body['status'] ?? 'ready'), ['degraded', 'failed'], true),
                'external_status' => $body['external_status'] ?? $body['status'] ?? null,
                'provider_task_id' => $providerJobId,
                'provider_job_id' => $providerJobId,
                'output_url' => $body['output_url'] ?? $body['video_url'] ?? $body['avatar_url'] ?? null,
                'raw_response' => $body,
                'error_code' => $body['error_code'] ?? null,
                'error_message' => $body['error_message'] ?? $body['message'] ?? null,
            ];
        } catch (\Throwable $exception) {
            return [
                ...$contract,
                'status' => 'degraded',
                'mode' => 'real_adapter_attempt',
                'fallback_active' => true,
                'error_code' => strtoupper($capability) . '_CLIENT_ERROR',
                'error_message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function generateFashnTryOn(array $payload): array
    {
        $model = (string) config('ai.tryon_model', 'tryon-max');
        $endpoint = (string) config('ai.tryon_create_endpoint', '/run');
        $contract = $this->tryOnContract(
            provider: 'fashn',
            model: $model,
            endpoint: $endpoint,
            requestId: $payload['request_id'] ?? null,
        );

        $unavailable = $this->providerUnavailable($contract, config('ai.tryon_api_base_url'), config('ai.tryon_api_key'), 'TRYON_PROVIDER_NOT_CONFIGURED');
        if ($unavailable !== null) {
            return $unavailable;
        }

        $baseUrl = rtrim((string) config('ai.tryon_api_base_url'), '/');
        $apiKey = (string) config('ai.tryon_api_key');

        try {
            $response = Http::timeout((int) config('ai.timeout_seconds', 30))
                ->withHeaders($this->providerHeaders($apiKey))
                ->post($baseUrl . $endpoint, [
                    'model_name' => $model,
                    'inputs' => [
                        'model_image' => $payload['person_image_url'] ?? null,
                        'product_image' => $payload['clothing_image_url'] ?? null,
                        'prompt' => (string) config('ai.tryon_prompt'),
                        'resolution' => (string) config('ai.tryon_resolution', '1k'),
                        'generation_mode' => (string) config('ai.tryon_generation_mode', 'balanced'),
                        'num_images' => 1,
                        'output_format' => (string) config('ai.tryon_output_format', 'png'),
                        'return_base64' => (bool) config('ai.tryon_return_base64', false),
                    ],
                ]);

            if (! $response->successful()) {
                return [
                    ...$contract,
                    'status' => 'degraded',
                    'mode' => 'real_adapter_attempt',
                    'fallback_active' => true,
                    'http_status' => $response->status(),
                    'error_code' => 'TRY_ON_HTTP_ERROR',
                    'error_message' => 'External provider returned HTTP ' . $response->status() . '.',
                ];
            }

            $body = $response->json() ?? [];
            $body = is_array($body) ? $body : [];
            $error = is_array($body) ? ($body['error'] ?? null) : null;

            if (is_array($error) || is_string($error)) {
                return [
                    ...$contract,
                    'status' => 'failed',
                    'mode' => 'external',
                    'fallback_active' => true,
                    'provider_task_id' => is_array($body) ? ($body['id'] ?? null) : null,
                    'provider_job_id' => is_array($body) ? ($body['id'] ?? null) : null,
                    'output_url' => null,
                    'error_code' => is_array($error) ? ($error['name'] ?? 'FASHN_CREATE_ERROR') : 'FASHN_CREATE_ERROR',
                    'error_message' => is_array($error) ? ($error['message'] ?? 'FASHN returned an error while creating the try-on task.') : $error,
                    'raw_response' => $body,
                ];
            }

            $providerTaskId = is_array($body) ? ($body['id'] ?? null) : null;

            if (! is_string($providerTaskId) || $providerTaskId === '') {
                return [
                    ...$contract,
                    'status' => 'degraded',
                    'mode' => 'real_adapter_attempt',
                    'fallback_active' => true,
                    'output_url' => null,
                    'error_code' => 'FASHN_TASK_ID_MISSING',
                    'error_message' => 'FASHN did not return a task id.',
                    'raw_response' => $body,
                ];
            }

            return [
                ...$contract,
                'status' => 'processing',
                'mode' => 'external',
                'external_status' => 'processing',
                'fallback_active' => false,
                'provider_task_id' => $providerTaskId,
                'provider_job_id' => $providerTaskId,
                'output_url' => null,
                'raw_response' => $body,
                'error_code' => null,
                'error_message' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                ...$contract,
                'status' => 'degraded',
                'mode' => 'real_adapter_attempt',
                'fallback_active' => true,
                'error_code' => 'TRY_ON_CLIENT_ERROR',
                'error_message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function pollFashnTryOn(string $providerTaskId, ?string $requestId): array
    {
        $model = (string) config('ai.tryon_model', 'tryon-max');
        $endpoint = str_replace('{id}', rawurlencode($providerTaskId), (string) config('ai.tryon_status_endpoint', '/status/{id}'));
        $contract = $this->tryOnContract(
            provider: 'fashn',
            model: $model,
            endpoint: $endpoint,
            requestId: $requestId,
        );

        $unavailable = $this->providerUnavailable($contract, config('ai.tryon_api_base_url'), config('ai.tryon_api_key'), 'TRYON_PROVIDER_NOT_CONFIGURED');
        if ($unavailable !== null) {
            return [
                ...$unavailable,
                'provider_task_id' => $providerTaskId,
                'provider_job_id' => $providerTaskId,
            ];
        }

        $baseUrl = rtrim((string) config('ai.tryon_api_base_url'), '/');
        $apiKey = (string) config('ai.tryon_api_key');

        try {
            $response = Http::timeout((int) config('ai.timeout_seconds', 30))
                ->withHeaders($this->providerHeaders($apiKey))
                ->get($baseUrl . $endpoint);

            if (! $response->successful()) {
                return [
                    ...$contract,
                    'status' => 'degraded',
                    'mode' => 'real_adapter_attempt',
                    'fallback_active' => true,
                    'provider_task_id' => $providerTaskId,
                    'provider_job_id' => $providerTaskId,
                    'http_status' => $response->status(),
                    'error_code' => 'TRY_ON_STATUS_HTTP_ERROR',
                    'error_message' => 'External provider returned HTTP ' . $response->status() . ' while checking task status.',
                ];
            }

            $body = $response->json() ?? [];
            $body = is_array($body) ? $body : [];

            return $this->normalizeFashnStatus($contract, $providerTaskId, $body);
        } catch (\Throwable $exception) {
            return [
                ...$contract,
                'status' => 'degraded',
                'mode' => 'real_adapter_attempt',
                'fallback_active' => true,
                'provider_task_id' => $providerTaskId,
                'provider_job_id' => $providerTaskId,
                'error_code' => 'TRY_ON_STATUS_CLIENT_ERROR',
                'error_message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $contract
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function normalizeFashnStatus(array $contract, string $providerTaskId, array $body): array
    {
        $externalStatus = (string) ($body['status'] ?? 'unknown');
        $output = $body['output'] ?? [];
        $outputUrl = is_array($output) ? ($output[0] ?? null) : null;
        $error = $body['error'] ?? null;

        if ($externalStatus === 'completed') {
            return [
                ...$contract,
                'status' => 'success',
                'mode' => 'external',
                'external_status' => $externalStatus,
                'fallback_active' => false,
                'provider_task_id' => $providerTaskId,
                'provider_job_id' => $providerTaskId,
                'output_url' => $outputUrl,
                'raw_response' => $body,
                'error_code' => null,
                'error_message' => null,
            ];
        }

        if (in_array($externalStatus, ['processing', 'queued', 'starting', 'running'], true)) {
            return [
                ...$contract,
                'status' => 'processing',
                'mode' => 'external',
                'external_status' => $externalStatus,
                'fallback_active' => false,
                'provider_task_id' => $providerTaskId,
                'provider_job_id' => $providerTaskId,
                'output_url' => null,
                'raw_response' => $body,
                'error_code' => null,
                'error_message' => null,
            ];
        }

        if ($externalStatus === 'failed') {
            return [
                ...$contract,
                'status' => 'failed',
                'mode' => 'external',
                'external_status' => $externalStatus,
                'fallback_active' => true,
                'provider_task_id' => $providerTaskId,
                'provider_job_id' => $providerTaskId,
                'output_url' => null,
                'raw_response' => $body,
                'error_code' => is_array($error) ? ($error['name'] ?? 'FASHN_STATUS_FAILED') : 'FASHN_STATUS_FAILED',
                'error_message' => is_array($error) ? ($error['message'] ?? 'FASHN try-on task failed.') : 'FASHN try-on task failed.',
            ];
        }

        return [
            ...$contract,
            'status' => 'degraded',
            'mode' => 'real_adapter_attempt',
            'external_status' => $externalStatus,
            'fallback_active' => true,
            'provider_task_id' => $providerTaskId,
            'provider_job_id' => $providerTaskId,
            'output_url' => null,
            'raw_response' => $body,
            'error_code' => 'FASHN_STATUS_UNKNOWN',
            'error_message' => 'FASHN returned an unknown task status.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tryOnContract(string $provider, string $model, string $endpoint, mixed $requestId): array
    {
        return [
            'schema_version' => 'v1',
            'request_id' => is_string($requestId) ? $requestId : null,
            'capability' => 'try_on',
            'provider' => $provider,
            'model' => $model,
            'endpoint' => $endpoint,
            'api_key_configured' => (string) config('ai.tryon_api_key') !== '',
            'external_provider_calls_enabled' => (bool) config('ai.external_provider_calls_enabled', false),
        ];
    }

    /**
     * @param array<string, mixed> $contract
     * @return array<string, mixed>|null
     */
    private function providerUnavailable(array $contract, mixed $baseUrl, mixed $apiKey, string $missingCode): ?array
    {
        $baseUrl = rtrim((string) $baseUrl, '/');
        $apiKey = (string) $apiKey;

        if (! (bool) config('ai.external_provider_calls_enabled', false)) {
            return [
                ...$contract,
                'status' => 'degraded',
                'mode' => 'fallback',
                'fallback_active' => true,
                'error_code' => 'EXTERNAL_PROVIDER_CALLS_DISABLED',
                'error_message' => 'External provider calls are disabled. Set AI_EXTERNAL_PROVIDER_CALLS=true after manual provider verification.',
            ];
        }

        if ($baseUrl === '' || $apiKey === '') {
            return [
                ...$contract,
                'status' => 'degraded',
                'mode' => 'fallback',
                'fallback_active' => true,
                'error_code' => $missingCode,
                'error_message' => 'External provider base URL or API key is not configured.',
            ];
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function providerHeaders(string $apiKey): array
    {
        return [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
