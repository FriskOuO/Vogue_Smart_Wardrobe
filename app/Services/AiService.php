<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class AiService
{
    protected string $baseUrl;
    protected string $internalToken;
    protected int $timeoutSeconds;
    protected bool $mockMode;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('ai.service_url'), '/');
        $this->internalToken = config('ai.internal_token');
        $this->timeoutSeconds = (int) config('ai.timeout_seconds', 30);
        $this->mockMode = (bool) config('ai.mock_mode', true);
    }

    /**
     * 共用 AI Service POST 方法
     */
    private function post(string $endpoint, array $payload, ?int $timeout = null): array
    {
        try {
            $response = Http::timeout($timeout ?? $this->timeoutSeconds)
                ->withHeaders([
                    'X-Internal-AI-Token' => $this->internalToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . $endpoint, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'schema_version' => $payload['schema_version'] ?? 'v1',
                'request_id' => $payload['request_id'] ?? null,
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_HTTP_ERROR',
                    'message' => 'AI Service 回傳非成功 HTTP 狀態碼',
                    'details' => [
                        'http_status' => $response->status(),
                        'body' => $response->body(),
                    ],
                ],
            ];
        } catch (ConnectionException $e) {
            return [
                'schema_version' => $payload['schema_version'] ?? 'v1',
                'request_id' => $payload['request_id'] ?? null,
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_SERVICE_UNAVAILABLE',
                    'message' => '無法連線到 AI Service',
                    'details' => [
                        'exception' => $e->getMessage(),
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'schema_version' => $payload['schema_version'] ?? 'v1',
                'request_id' => $payload['request_id'] ?? null,
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_INTERNAL_CLIENT_ERROR',
                    'message' => 'Laravel 呼叫 AI Service 時發生錯誤',
                    'details' => [
                        'exception' => $e->getMessage(),
                    ],
                ],
            ];
        }
    }

    /**
     * POST /ai/attributes
     * 衣物屬性辨識
     */
    public function analyzeAttributes(array $data): array
    {
        $payload = [
            'schema_version' => 'v1',
            'request_id' => $data['request_id'] ?? $this->makeRequestId('attributes'),
            'user_id' => $data['user_id'],
            'clothing_id' => $data['clothing_id'],
            'image_path' => $data['image_path'],
            'image_url' => $data['image_url'] ?? null,
            'locale' => $data['locale'] ?? app()->getLocale(),
            'mock_mode' => $data['mock_mode'] ?? $this->mockMode,
        ];

        return $this->post('/ai/attributes', $payload, 30);
    }

    /**
     * POST /ai/embed/image
     * 產生 image embedding
     */
    public function embedImage(array $data): array
    {
        $payload = [
            'schema_version' => 'v1',
            'request_id' => $data['request_id'] ?? $this->makeRequestId('embed_image'),
            'user_id' => $data['user_id'],
            'clothing_id' => $data['clothing_id'],
            'image_path' => $data['image_path'],
            'image_url' => $data['image_url'] ?? null,
            'model' => $data['model'] ?? 'clip-vit-base-patch32',
            'store_to_vector_db' => $data['store_to_vector_db'] ?? true,
            'mock_mode' => $data['mock_mode'] ?? $this->mockMode,
        ];

        return $this->post('/ai/embed/image', $payload, 30);
    }

    /**
     * POST /ai/embed/text
     * 產生 text embedding
     */
    public function embedText(array $data): array
    {
        $payload = [
            'schema_version' => 'v1',
            'request_id' => $data['request_id'] ?? $this->makeRequestId('embed_text'),
            'user_id' => $data['user_id'],
            'query' => $data['query'],
            'locale' => $data['locale'] ?? app()->getLocale(),
            'model' => $data['model'] ?? 'clip-vit-base-patch32',
            'mock_mode' => $data['mock_mode'] ?? $this->mockMode,
        ];

        return $this->post('/ai/embed/text', $payload, 15);
    }

    /**
     * POST /ai/search/similar
     * 相似搜尋 topK
     */
    public function searchSimilar(array $data): array
    {
        $payload = [
            'schema_version' => 'v1',
            'request_id' => $data['request_id'] ?? $this->makeRequestId('search_similar'),
            'user_id' => $data['user_id'],
            'query_type' => $data['query_type'],
            'query' => $data['query'] ?? null,
            'source_clothing_id' => $data['source_clothing_id'] ?? null,
            'embedding' => $data['embedding'],
            'top_k' => $data['top_k'] ?? 5,
            'filters' => empty($data['filters']) ? new \stdClass() : $data['filters'],
            'fallback_enabled' => $data['fallback_enabled'] ?? true,
            'mock_mode' => $data['mock_mode'] ?? $this->mockMode,
        ];

        return $this->post('/ai/search/similar', $payload, 15);
    }

    /**
     * POST /ai/pose
     * 人體姿態 keypoints
     */
    public function analyzePose(array $data): array
    {
        $payload = [
            'schema_version' => 'v1',
            'request_id' => $data['request_id'] ?? $this->makeRequestId('pose'),
            'user_id' => $data['user_id'],
            'image_path' => $data['image_path'],
            'image_url' => $data['image_url'] ?? null,
            'task_type' => $data['task_type'] ?? 'magic_mirror',
            'return_annotated_image' => $data['return_annotated_image'] ?? true,
            'mock_mode' => $data['mock_mode'] ?? $this->mockMode,
        ];

        return $this->post('/ai/pose', $payload, 30);
    }

    /**
     * 產生追蹤用 request_id
     */
    private function makeRequestId(string $type): string
    {
        return 'req_' . now()->format('Ymd_His') . '_' . $type . '_' . uniqid();
    }
}