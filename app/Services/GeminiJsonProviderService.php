<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiJsonProviderService
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function generate(
        string $adapter,
        string $model,
        string $prompt,
        array $payload = [],
        float $temperature = 0.25,
    ): array {
        $apiKey = (string) config('ai.gemini_api_key', '');

        if ($apiKey === '') {
            return $this->degraded(
                adapter: $adapter,
                model: $model,
                code: 'GEMINI_API_KEY_MISSING',
                message: 'GEMINI_API_KEY is not configured.',
            );
        }

        $modelPath = str_starts_with($model, 'models/') ? $model : 'models/' . $model;
        $endpoint = rtrim((string) config('ai.gemini_api_base_url'), '/')
            . '/v1beta/' . $modelPath . ':generateContent';

        try {
            $response = Http::timeout((int) config('ai.timeout_seconds', 30))
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => $prompt . "\n\nPayload:\n"
                                        . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => $temperature,
                    ],
                ]);

            if (! $response->successful()) {
                return $this->degraded(
                    adapter: $adapter,
                    model: $model,
                    code: 'GEMINI_HTTP_ERROR',
                    message: 'Gemini API returned HTTP ' . $response->status() . '.',
                    extra: ['http_status' => $response->status()],
                );
            }

            $text = collect($response->json('candidates.0.content.parts', []))
                ->pluck('text')
                ->filter()
                ->implode("\n");
            $decoded = json_decode($text, true);

            if (! is_array($decoded)) {
                return $this->degraded(
                    adapter: $adapter,
                    model: $model,
                    code: 'GEMINI_RESPONSE_PARSE_FAILED',
                    message: 'Gemini response did not contain valid JSON.',
                );
            }

            return [
                'provider' => 'gemini',
                'adapter' => $adapter,
                'status' => 'ready',
                'mode' => 'real_adapter',
                'model' => $model,
                'fallback_active' => false,
                'endpoint' => '/v1beta/' . $modelPath . ':generateContent',
                'result' => $decoded,
                'error_code' => null,
                'error_message' => null,
            ];
        } catch (\Throwable $exception) {
            return $this->degraded(
                adapter: $adapter,
                model: $model,
                code: 'GEMINI_CLIENT_ERROR',
                message: $exception->getMessage(),
            );
        }
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function degraded(string $adapter, string $model, string $code, string $message, array $extra = []): array
    {
        return [
            'provider' => 'gemini',
            'adapter' => $adapter,
            'status' => 'degraded',
            'mode' => 'real_adapter_attempt',
            'model' => $model,
            'fallback_active' => true,
            'result' => null,
            'error_code' => $code,
            'error_message' => $message,
            ...$extra,
        ];
    }
}
