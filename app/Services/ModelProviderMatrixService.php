<?php

namespace App\Services;

class ModelProviderMatrixService
{
    /**
     * @return array{ok: bool, failed: int, warnings: int, providers: array<int, array<string, mixed>>}
     */
    public function summary(): array
    {
        $providers = [
            $this->geminiStylist(),
            $this->geminiChatAssistant(),
            $this->geminiTextUnderstanding(),
            $this->clipTextSearch(),
            $this->clipImageEmbedding(),
            $this->blipClothingCaption(),
            $this->blipVqa(),
            $this->fashionAttributeClassifier(),
            $this->yoloPoseProvider(),
            $this->qdrantVectorStore(),
            $this->tryOnProvider(),
            $this->runwayVeoProvider(),
            $this->digitalTwinProvider(),
        ];

        $failed = collect($providers)->where('status', 'fail')->count();
        $warnings = collect($providers)->where('status', 'warn')->count();

        return [
            'ok' => $failed === 0,
            'failed' => $failed,
            'warnings' => $warnings,
            'providers' => $providers,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function geminiStylist(): array
    {
        $hasService = file_exists(base_path('app/Services/StylistTextGenerationService.php'));
        $hasModel = filled((string) config('ai.gemini_text_model'));
        $hasKey = filled((string) config('ai.gemini_api_key'));

        return $this->provider(
            capability: 'Gemini: 穿搭顧問',
            targetProvider: (string) config('ai.text_generation_provider', 'gemini'),
            targetModel: (string) config('ai.gemini_text_model', ''),
            status: $hasService && $hasModel ? ($hasKey ? 'pass' : 'warn') : 'fail',
            message: match (true) {
                ! $hasService => 'Stylist text generation service is missing.',
                ! $hasModel => 'GEMINI_TEXT_MODEL is missing.',
                ! $hasKey => 'Gemini stylist adapter is wired; set GEMINI_API_KEY for live calls.',
                default => 'Gemini stylist adapter is configured for live calls.',
            },
            adapter: 'gemini-stylist-text-v1',
            configKeys: ['AI_TEXT_GENERATION_PROVIDER', 'GEMINI_TEXT_MODEL', 'GEMINI_API_KEY', 'GEMINI_API_BASE_URL'],
            requiredForProduction: true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function geminiChatAssistant(): array
    {
        $provider = (string) config('ai.chat_provider', '');
        $model = (string) config('ai.chat_model', '');
        $hasService = file_exists(base_path('app/Services/GeminiChatAssistantService.php'));
        $hasKey = filled((string) config('ai.gemini_api_key'));

        return $this->provider(
            capability: 'Gemini: 聊天助理',
            targetProvider: $provider,
            targetModel: $model,
            status: $provider === 'gemini' && $model !== '' && $hasService ? ($hasKey ? 'pass' : 'warn') : 'fail',
            message: match (true) {
                ! $hasService => 'Gemini chat assistant service is missing.',
                $provider !== 'gemini' || $model === '' => 'AI_CHAT_PROVIDER or AI_CHAT_MODEL is missing.',
                ! $hasKey => 'Gemini chat assistant adapter is wired; set GEMINI_API_KEY for live calls.',
                default => 'Gemini chat assistant adapter is configured for live calls.',
            },
            adapter: 'gemini-chat-assistant-v1',
            configKeys: ['AI_CHAT_PROVIDER', 'AI_CHAT_MODEL', 'GEMINI_API_KEY'],
            requiredForProduction: false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function geminiTextUnderstanding(): array
    {
        $provider = (string) config('ai.text_understanding_provider', '');
        $model = (string) config('ai.text_understanding_model', '');
        $hasService = file_exists(base_path('app/Services/GeminiTextUnderstandingService.php'));
        $hasKey = filled((string) config('ai.gemini_api_key'));

        return $this->provider(
            capability: 'Gemini: 文字理解',
            targetProvider: $provider,
            targetModel: $model,
            status: $provider === 'gemini' && $model !== '' && $hasService ? ($hasKey ? 'pass' : 'warn') : 'fail',
            message: match (true) {
                ! $hasService => 'Gemini text understanding service is missing.',
                $provider !== 'gemini' || $model === '' => 'AI_TEXT_UNDERSTANDING_PROVIDER or AI_TEXT_UNDERSTANDING_MODEL is missing.',
                ! $hasKey => 'Gemini text understanding adapter is wired; set GEMINI_API_KEY for live calls.',
                default => 'Gemini text understanding adapter is configured for live calls.',
            },
            adapter: 'gemini-text-understanding-v1',
            configKeys: ['AI_TEXT_UNDERSTANDING_PROVIDER', 'AI_TEXT_UNDERSTANDING_MODEL', 'GEMINI_API_KEY'],
            requiredForProduction: false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function clipTextSearch(): array
    {
        return $this->pythonProvider(
            capability: 'CLIP: 文字搜尋',
            targetProvider: 'clip',
            targetModel: 'local fashion_search_finetuned/model',
            adapter: 'clip-embedding-v1:text',
            files: [
                'ai_service/services/clip_embedding_service.py',
                'ai_service/routes/ai_routes.py',
            ],
            configKeys: ['EMBEDDING_PROVIDER', 'EMBEDDING_MODEL_REPOSITORY'],
            message: 'Text embedding adapter is wired to the local fine-tuned CLIP repository configured by EMBEDDING_MODEL_REPOSITORY.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function clipImageEmbedding(): array
    {
        return $this->pythonProvider(
            capability: 'CLIP: 圖片向量',
            targetProvider: 'clip',
            targetModel: 'local fashion_search_finetuned/model',
            adapter: 'clip-embedding-v1:image',
            files: [
                'ai_service/services/clip_embedding_service.py',
                'ai_service/routes/ai_routes.py',
            ],
            configKeys: ['EMBEDDING_PROVIDER', 'EMBEDDING_MODEL_REPOSITORY'],
            message: 'Image embedding adapter is wired to the local fine-tuned CLIP repository and returns 512D vectors for Qdrant.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function blipClothingCaption(): array
    {
        return $this->pythonProvider(
            capability: 'BLIP: 衣物描述',
            targetProvider: 'blip',
            targetModel: 'local Salesforce/blip-image-captioning-large',
            adapter: 'blip-image-caption-v1',
            files: [
                'ai_service/services/blip_caption_service.py',
                'ai_service/routes/ai_routes.py',
            ],
            configKeys: ['IMAGE_CAPTION_PROVIDER', 'IMAGE_CAPTION_MODEL_REPOSITORY'],
            message: 'BLIP Large caption adapter is wired through IMAGE_CAPTION_MODEL_REPOSITORY.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function blipVqa(): array
    {
        return $this->pythonProvider(
            capability: 'BLIP VQA: 進階衣物理解',
            targetProvider: 'blip_vqa',
            targetModel: 'local Salesforce/blip-vqa-base',
            adapter: 'blip-vqa-v1',
            files: [
                'ai_service/services/blip_vqa_service.py',
                'ai_service/routes/ai_routes.py',
            ],
            configKeys: ['VQA_PROVIDER', 'VQA_MODEL_REPOSITORY'],
            message: 'BLIP VQA endpoint is wired for garment type, color, pattern, and material questions.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fashionAttributeClassifier(): array
    {
        return $this->pythonProvider(
            capability: '多輸出分類: 衣物自動標籤',
            targetProvider: 'fashion_multioutput',
            targetModel: 'local fashion_multioutput_v4_smart_tuned.pth',
            adapter: 'fashion-attribute-classifier-v1',
            files: [
                'ai_service/services/fashion_attribute_service.py',
                'ai_service/services/adapter_orchestration_service.py',
            ],
            configKeys: ['ATTRIBUTE_PROVIDER', 'ATTRIBUTE_MODEL_REPOSITORY'],
            message: 'Multi-output classifier is integrated into /ai/attributes and enriches category, color, season, occasion, style, material, and pattern tags.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function yoloPoseProvider(): array
    {
        return $this->pythonProvider(
            capability: 'YOLO Pose: 姿態檢查',
            targetProvider: 'yolo_pose',
            targetModel: 'local yolo11s-pose.pt',
            adapter: 'yolo-pose-v1',
            files: [
                'ai_service/services/yolo_pose_service.py',
                'ai_service/routes/ai_routes.py',
            ],
            configKeys: ['POSE_PROVIDER', 'POSE_MODEL_REPOSITORY'],
            message: 'YOLO Pose is wired to /ai/pose and replaces the mock pose checker when AI_MOCK_MODE=false.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function qdrantVectorStore(): array
    {
        $hasService = file_exists(base_path('ai_service/services/vector_store_service.py'));
        $hasLauncher = file_exists(base_path('start-qdrant.ps1'));

        return $this->provider(
            capability: 'Qdrant: 正式向量資料庫',
            targetProvider: 'qdrant',
            targetModel: 'vogueai_clothing_embeddings / 512D cosine',
            status: $hasService && $hasLauncher ? 'pass' : 'fail',
            message: $hasService && $hasLauncher
                ? 'Qdrant contract, preflight, ensure-collection, upsert and search methods exist; daemon/collection readiness is verified by provider runtime smoke.'
                : 'Qdrant service or launcher is missing.',
            adapter: 'qdrant-vector-store-v1',
            configKeys: ['VECTOR_STORE_PROVIDER', 'VECTOR_STORE_URL', 'VECTOR_STORE_COLLECTION', 'VECTOR_STORE_TARGET_VECTOR_SIZE'],
            requiredForProduction: true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function tryOnProvider(): array
    {
        $provider = (string) config('ai.tryon_provider', 'external_tryon');
        $hasBaseUrl = filled((string) config('ai.tryon_api_base_url'));
        $hasKey = filled((string) config('ai.tryon_api_key'));
        $hasModel = filled((string) config('ai.tryon_model'));
        $callsEnabled = (bool) config('ai.external_provider_calls_enabled', false);
        $keyRequired = ! in_array($provider, ['huggingface_idm_vton'], true);
        $credentialReady = $hasKey || ! $keyRequired;

        return $this->provider(
            capability: 'Try-on: 真實換裝模型',
            targetProvider: $provider,
            targetModel: (string) config('ai.tryon_model', ''),
            status: $hasBaseUrl && $credentialReady && $hasModel && $callsEnabled ? 'pass' : 'warn',
            message: match (true) {
                ! $callsEnabled => 'Try-on provider contract exists; set AI_EXTERNAL_PROVIDER_CALLS=true after manual provider verification.',
                $hasBaseUrl && $credentialReady && $hasModel => $keyRequired
                    ? 'Try-on provider endpoint, credential, and live-call switch are configured.'
                    : 'Hugging Face IDM-VTON endpoint and live-call switch are configured; token is optional for the public Space.',
                default => 'Try-on provider contract exists; set AI_TRYON_API_BASE_URL and AI_TRYON_API_KEY to enable live calls.',
            },
            adapter: 'virtual-tryon-provider-v1',
            configKeys: ['AI_TRYON_PROVIDER', 'AI_TRYON_MODEL', 'AI_TRYON_API_BASE_URL', 'AI_TRYON_API_KEY', 'AI_EXTERNAL_PROVIDER_CALLS'],
            requiredForProduction: false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function runwayVeoProvider(): array
    {
        $provider = (string) config('ai.video_provider', 'veo');
        $hasModel = filled((string) config('ai.video_model'));
        $hasCredential = $provider === 'runway'
            ? filled((string) config('ai.runway_api_key'))
            : filled((string) config('ai.veo_api_key'));
        $callsEnabled = (bool) config('ai.external_provider_calls_enabled', false);

        return $this->provider(
            capability: 'Runway / Veo: 真實影片生成',
            targetProvider: $provider,
            targetModel: (string) config('ai.video_model', ''),
            status: $hasModel && $hasCredential && $callsEnabled ? 'pass' : 'warn',
            message: match (true) {
                ! $callsEnabled => 'Video provider contract exists; set AI_EXTERNAL_PROVIDER_CALLS=true after manual provider verification.',
                $hasModel && $hasCredential => 'Video provider model, credential, and live-call switch are configured.',
                default => 'Video provider contract exists; set VEO_API_KEY or RUNWAY_API_KEY before live generation.',
            },
            adapter: 'video-generation-provider-v1',
            configKeys: ['AI_VIDEO_PROVIDER', 'AI_VIDEO_MODEL', 'VEO_API_KEY', 'RUNWAY_API_KEY', 'AI_EXTERNAL_PROVIDER_CALLS'],
            requiredForProduction: false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function digitalTwinProvider(): array
    {
        $hasBaseUrl = filled((string) config('ai.digital_twin_api_base_url'));
        $hasKey = filled((string) config('ai.digital_twin_api_key'));
        $hasModel = filled((string) config('ai.digital_twin_model'));
        $callsEnabled = (bool) config('ai.external_provider_calls_enabled', false);

        return $this->provider(
            capability: 'Digital Twin: 3D / 多視角 / avatar provider',
            targetProvider: (string) config('ai.digital_twin_provider', 'avatar_3d'),
            targetModel: (string) config('ai.digital_twin_model', ''),
            status: $hasBaseUrl && $hasKey && $hasModel && $callsEnabled ? 'pass' : 'warn',
            message: match (true) {
                ! $callsEnabled => 'Digital Twin provider contract exists; set AI_EXTERNAL_PROVIDER_CALLS=true after manual provider verification.',
                $hasBaseUrl && $hasKey && $hasModel => 'Digital Twin avatar provider endpoint, credential, and live-call switch are configured.',
                default => 'Digital Twin provider contract exists; set AI_DIGITAL_TWIN_API_BASE_URL and AI_DIGITAL_TWIN_API_KEY to enable live avatar generation.',
            },
            adapter: 'digital-twin-avatar-provider-v1',
            configKeys: ['AI_DIGITAL_TWIN_PROVIDER', 'AI_DIGITAL_TWIN_MODEL', 'AI_DIGITAL_TWIN_API_BASE_URL', 'AI_DIGITAL_TWIN_API_KEY', 'AI_EXTERNAL_PROVIDER_CALLS'],
            requiredForProduction: false,
        );
    }

    /**
     * @param array<int, string> $files
     * @param array<int, string> $configKeys
     * @return array<string, mixed>
     */
    private function pythonProvider(
        string $capability,
        string $targetProvider,
        string $targetModel,
        string $adapter,
        array $files,
        array $configKeys,
        string $message,
    ): array {
        $missing = collect($files)
            ->reject(fn (string $file) => file_exists(base_path($file)))
            ->values()
            ->all();

        return $this->provider(
            capability: $capability,
            targetProvider: $targetProvider,
            targetModel: $targetModel,
            status: empty($missing) ? 'pass' : 'fail',
            message: empty($missing)
                ? $message
                : 'Missing provider files: ' . implode(', ', $missing),
            adapter: $adapter,
            configKeys: $configKeys,
            requiredForProduction: true,
        );
    }

    /**
     * @param array<int, string> $configKeys
     * @return array<string, mixed>
     */
    private function provider(
        string $capability,
        string $targetProvider,
        string $targetModel,
        string $status,
        string $message,
        string $adapter,
        array $configKeys,
        bool $requiredForProduction,
    ): array {
        return [
            'capability' => $capability,
            'status' => $status,
            'target_provider' => $targetProvider,
            'target_model' => $targetModel,
            'adapter' => $adapter,
            'config_keys' => $configKeys,
            'required_for_production' => $requiredForProduction,
            'message' => $message,
        ];
    }
}
