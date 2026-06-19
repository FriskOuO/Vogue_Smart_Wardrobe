<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class StylistTextGenerationService
{
    public function generate(array $payload): array
    {
        $fallbackResponse = $this->ruleBasedResponse($payload);

        if ($payload['mock_mode'] ?? true) {
            return $fallbackResponse;
        }

        return $this->generateWithGemini($payload, $fallbackResponse);
    }

    private function ruleBasedResponse(array $payload): array
    {
        $context = $payload['context'] ?? [];
        $selectedItems = $payload['selected_items'] ?? [];
        $digitalTwinProfile = $payload['digital_twin_profile'] ?? null;
        $embeddingSignals = $payload['embedding_signals'] ?? [];

        $occasion = (string) ($context['occasion'] ?? 'daily outfit');
        $weather = (string) ($context['weather'] ?? 'not specified');
        $stylePreference = (string) ($context['style_preference'] ?? 'balanced style');
        $itemNames = collect($selectedItems)
            ->pluck('name')
            ->filter()
            ->values()
            ->implode(', ');

        if ($itemNames === '') {
            $itemNames = 'available closet items';
        }

        return [
            'title' => $this->buildTitle($occasion, $stylePreference),
            'summary' => sprintf(
                'For %s in %s, the mock Gemini text adapter would explain why %s fits the requested %s direction.',
                $occasion,
                $weather,
                $itemNames,
                $stylePreference
            ),
            'styling_tips' => $this->buildStylingTips($selectedItems, $digitalTwinProfile, $embeddingSignals),
            'text_generation' => [
                'provider' => config('ai.text_generation_provider', 'gemini'),
                'adapter' => 'gemini-stylist-text-v1',
                'status' => 'planned',
                'mode' => 'mock',
                'model' => config('ai.gemini_text_model', 'gemini-2.5-flash'),
                'fallback' => 'rule_based_text',
                'fallback_active' => true,
                'degraded_reason' => 'GEMINI_TEXT_ADAPTER_NOT_CONNECTED',
                'error_code' => null,
                'error_message' => null,
                'prompt_contract' => [
                    'inputs' => [
                        'context',
                        'selected_items',
                        'digital_twin_profile',
                        'embedding_signals',
                        'feedback_history',
                    ],
                    'outputs' => [
                        'title',
                        'summary',
                        'styling_tips',
                        'reasoning_notes',
                    ],
                    'guardrails' => [
                        'Do not invent closet items that are not in selected_items.',
                        'Keep output concise and demo-safe.',
                        'Return structured JSON so Laravel can persist it without parsing prose.',
                    ],
                ],
            ],
        ];
    }

    private function generateWithGemini(array $payload, array $fallbackResponse): array
    {
        $apiKey = (string) config('ai.gemini_api_key', '');
        $model = (string) config('ai.gemini_text_model', 'gemini-2.5-flash');

        if ($apiKey === '') {
            return $this->attachGeminiAttempt($fallbackResponse, [
                'status' => 'degraded',
                'mode' => 'real_adapter_attempt',
                'model' => $model,
                'fallback_active' => true,
                'degraded_reason' => 'GEMINI_API_KEY_MISSING',
                'error_code' => 'GEMINI_API_KEY_MISSING',
                'error_message' => 'GEMINI_API_KEY is not configured.',
            ]);
        }

        $modelPath = str_starts_with($model, 'models/') ? $model : 'models/' . $model;
        $endpoint = rtrim((string) config('ai.gemini_api_base_url'), '/')
            . '/v1beta/' . $modelPath . ':generateContent';

        try {
            $response = Http::timeout(30)
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
                                    'text' => $this->buildGeminiPrompt($payload),
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.35,
                    ],
                ]);

            if (! $response->successful()) {
                return $this->attachGeminiAttempt($fallbackResponse, [
                    'status' => 'degraded',
                    'mode' => 'real_adapter_attempt',
                    'model' => $model,
                    'fallback_active' => true,
                    'degraded_reason' => 'GEMINI_HTTP_ERROR',
                    'error_code' => 'GEMINI_HTTP_ERROR',
                    'error_message' => 'Gemini API returned HTTP ' . $response->status() . '.',
                    'http_status' => $response->status(),
                ]);
            }

            $text = collect($response->json('candidates.0.content.parts', []))
                ->pluck('text')
                ->filter()
                ->implode("\n");
            $decoded = json_decode($text, true);

            if (! is_array($decoded)) {
                return $this->attachGeminiAttempt($fallbackResponse, [
                    'status' => 'degraded',
                    'mode' => 'real_adapter_attempt',
                    'model' => $model,
                    'fallback_active' => true,
                    'degraded_reason' => 'GEMINI_RESPONSE_PARSE_FAILED',
                    'error_code' => 'GEMINI_RESPONSE_PARSE_FAILED',
                    'error_message' => 'Gemini response did not contain valid JSON.',
                ]);
            }

            return [
                'title' => (string) ($decoded['title'] ?? $fallbackResponse['title']),
                'summary' => (string) ($decoded['summary'] ?? $fallbackResponse['summary']),
                'styling_tips' => array_values(array_filter(
                    (array) ($decoded['styling_tips'] ?? $fallbackResponse['styling_tips'])
                )),
                'text_generation' => [
                    ...$fallbackResponse['text_generation'],
                    'status' => 'ready',
                    'mode' => 'real_adapter',
                    'model' => $model,
                    'fallback' => null,
                    'fallback_active' => false,
                    'degraded_reason' => null,
                    'error_code' => null,
                    'error_message' => null,
                    'endpoint' => '/v1beta/' . $modelPath . ':generateContent',
                    'reasoning_notes' => array_values((array) ($decoded['reasoning_notes'] ?? [])),
                ],
            ];
        } catch (\Throwable $exception) {
            return $this->attachGeminiAttempt($fallbackResponse, [
                'status' => 'degraded',
                'mode' => 'real_adapter_attempt',
                'model' => $model,
                'fallback_active' => true,
                'degraded_reason' => 'GEMINI_CLIENT_ERROR',
                'error_code' => 'GEMINI_CLIENT_ERROR',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    private function attachGeminiAttempt(array $fallbackResponse, array $attempt): array
    {
        $fallbackResponse['text_generation'] = [
            ...$fallbackResponse['text_generation'],
            ...$attempt,
            'provider' => config('ai.text_generation_provider', 'gemini'),
            'adapter' => 'gemini-stylist-text-v1',
            'fallback' => 'rule_based_text',
        ];

        return $fallbackResponse;
    }

    private function buildGeminiPrompt(array $payload): string
    {
        return 'You are VogueAI stylist. Return only JSON with keys: title, summary, styling_tips, reasoning_notes. '
            . 'Use only the selected_items, do not invent clothing. Payload: '
            . json_encode([
                'context' => $payload['context'] ?? [],
                'selected_items' => $payload['selected_items'] ?? [],
                'digital_twin_profile' => $payload['digital_twin_profile'] ?? null,
                'embedding_signals' => $payload['embedding_signals'] ?? [],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function buildTitle(string $occasion, string $stylePreference): string
    {
        return trim($occasion . ' x ' . $stylePreference . ' outfit recommendation');
    }

    private function buildStylingTips(array $selectedItems, ?array $digitalTwinProfile, array $embeddingSignals): array
    {
        $firstItem = collect($selectedItems)->first();
        $firstItemName = is_array($firstItem) ? ($firstItem['name'] ?? 'the top-ranked item') : 'the top-ranked item';
        $topEmbeddingMatch = collect($embeddingSignals['top_matches'] ?? [])->first();
        $embeddingTip = is_array($topEmbeddingMatch)
            ? sprintf('Use %s as the semantic anchor because it has the strongest embedding match.', $topEmbeddingMatch['name'] ?? $firstItemName)
            : 'Use the highest-confidence closet item as the outfit anchor.';

        $profileTip = $digitalTwinProfile
            ? sprintf(
                'Keep the recommendation aligned with the Digital Twin profile: %s, %s, %s.',
                $digitalTwinProfile['dominant_category'] ?? 'unknown category',
                $digitalTwinProfile['dominant_color'] ?? 'unknown color',
                $digitalTwinProfile['dominant_style'] ?? 'unknown style'
            )
            : 'If no Digital Twin profile exists, keep the copy transparent about rule-based fallback.';

        return [
            sprintf('Start with %s, then use the remaining items to support the requested occasion.', $firstItemName),
            $embeddingTip,
            $profileTip,
        ];
    }
}
