<?php

namespace App\Services;

class GeminiTextUnderstandingService
{
    public function __construct(
        private readonly GeminiJsonProviderService $gemini,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function understand(array $payload): array
    {
        $fallback = $this->fallbackUnderstanding($payload);

        if ($payload['mock_mode'] ?? true) {
            return $fallback;
        }

        $model = (string) config('ai.text_understanding_model', config('ai.gemini_text_model', 'gemini-2.5-flash'));
        $attempt = $this->gemini->generate(
            adapter: 'gemini-text-understanding-v1',
            model: $model,
            prompt: 'You are VogueAI text understanding provider. Return only JSON with keys: intent, normalized_query, entities, categories, colors, style_tags, confidence, safety_flags. Extract wardrobe search and styling intent from the user text.',
            payload: [
                'text' => $payload['text'] ?? '',
                'locale' => $payload['locale'] ?? 'zh_TW',
                'context' => $payload['context'] ?? [],
            ],
            temperature: 0.1,
        );

        if (($attempt['status'] ?? null) !== 'ready') {
            $fallback['understanding_generation'] = [
                ...$fallback['understanding_generation'],
                ...$attempt,
                'fallback' => 'rule_based_understanding',
            ];

            return $fallback;
        }

        $result = $attempt['result'] ?? [];

        return [
            'intent' => (string) ($result['intent'] ?? $fallback['intent']),
            'normalized_query' => (string) ($result['normalized_query'] ?? $fallback['normalized_query']),
            'entities' => array_values((array) ($result['entities'] ?? $fallback['entities'])),
            'categories' => array_values((array) ($result['categories'] ?? $fallback['categories'])),
            'colors' => array_values((array) ($result['colors'] ?? $fallback['colors'])),
            'style_tags' => array_values((array) ($result['style_tags'] ?? $fallback['style_tags'])),
            'confidence' => (float) ($result['confidence'] ?? $fallback['confidence']),
            'safety_flags' => array_values((array) ($result['safety_flags'] ?? [])),
            'understanding_generation' => [
                ...$fallback['understanding_generation'],
                ...$attempt,
                'fallback' => null,
                'result' => null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function fallbackUnderstanding(array $payload): array
    {
        $text = mb_strtolower(trim((string) ($payload['text'] ?? '')));
        $colors = collect(['white', 'black', 'navy', 'red', 'blue', '白', '黑', '藍', '紅'])
            ->filter(fn (string $color) => $text !== '' && str_contains($text, $color))
            ->values()
            ->all();
        $categories = collect(['shirt', 'dress', 'coat', 'blazer', '上衣', '洋裝', '外套'])
            ->filter(fn (string $category) => $text !== '' && str_contains($text, $category))
            ->values()
            ->all();

        return [
            'intent' => $text === '' ? 'unknown' : 'wardrobe_text_understanding',
            'normalized_query' => $text,
            'entities' => array_values(array_unique([...$colors, ...$categories])),
            'categories' => $categories,
            'colors' => $colors,
            'style_tags' => [],
            'confidence' => $text === '' ? 0.0 : 0.35,
            'safety_flags' => [],
            'understanding_generation' => [
                'provider' => config('ai.text_understanding_provider', 'gemini'),
                'adapter' => 'gemini-text-understanding-v1',
                'status' => 'planned',
                'mode' => 'mock',
                'model' => config('ai.text_understanding_model', config('ai.gemini_text_model', 'gemini-2.5-flash')),
                'fallback' => 'rule_based_understanding',
                'fallback_active' => true,
                'error_code' => null,
                'error_message' => null,
            ],
        ];
    }
}
