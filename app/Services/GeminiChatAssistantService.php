<?php

namespace App\Services;

class GeminiChatAssistantService
{
    public function __construct(
        private readonly GeminiJsonProviderService $gemini,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function reply(array $payload): array
    {
        $fallback = $this->fallbackReply($payload);

        if ($payload['mock_mode'] ?? true) {
            return $fallback;
        }

        $model = (string) config('ai.chat_model', config('ai.gemini_text_model', 'gemini-2.5-flash'));
        $attempt = $this->gemini->generate(
            adapter: 'gemini-chat-assistant-v1',
            model: $model,
            prompt: 'You are VogueAI chat assistant. Return only JSON with keys: reply, suggested_actions, safety_notes, context_summary. Keep the answer concise, wardrobe-focused, and do not invent closet items.',
            payload: [
                'message' => $payload['message'] ?? '',
                'locale' => $payload['locale'] ?? 'zh_TW',
                'closet_context' => $payload['closet_context'] ?? [],
                'conversation_context' => $payload['conversation_context'] ?? [],
            ],
            temperature: 0.35,
        );

        if (($attempt['status'] ?? null) !== 'ready') {
            $fallback['chat_generation'] = [
                ...$fallback['chat_generation'],
                ...$attempt,
                'fallback' => 'rule_based_chat',
            ];

            return $fallback;
        }

        $result = $attempt['result'] ?? [];

        return [
            'reply' => (string) ($result['reply'] ?? $fallback['reply']),
            'suggested_actions' => array_values((array) ($result['suggested_actions'] ?? $fallback['suggested_actions'])),
            'safety_notes' => array_values((array) ($result['safety_notes'] ?? [])),
            'context_summary' => (string) ($result['context_summary'] ?? ''),
            'chat_generation' => [
                ...$fallback['chat_generation'],
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
    private function fallbackReply(array $payload): array
    {
        $message = trim((string) ($payload['message'] ?? ''));
        $closetCount = count((array) ($payload['closet_context']['items'] ?? []));

        return [
            'reply' => $message === ''
                ? '請告訴我今天的場合、天氣或想要的風格，我會用你的衣櫥資料協助搭配。'
                : '我已收到你的問題。正式 Gemini 聊天助理未啟用時，會先依照衣櫥資料與安全規則提供保守建議。',
            'suggested_actions' => [
                $closetCount > 0 ? '從現有衣櫥挑選候選單品' : '先上傳幾件常穿衣物',
                '補充場合、天氣與正式程度',
            ],
            'safety_notes' => [
                '不會臆測不存在的衣物。',
            ],
            'context_summary' => $closetCount . ' closet items available.',
            'chat_generation' => [
                'provider' => config('ai.chat_provider', 'gemini'),
                'adapter' => 'gemini-chat-assistant-v1',
                'status' => 'planned',
                'mode' => 'mock',
                'model' => config('ai.chat_model', config('ai.gemini_text_model', 'gemini-2.5-flash')),
                'fallback' => 'rule_based_chat',
                'fallback_active' => true,
                'error_code' => null,
                'error_message' => null,
            ],
        ];
    }
}
