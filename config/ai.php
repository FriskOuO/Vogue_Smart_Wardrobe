<?php

return [
    'service_url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8001'),

    'internal_token' => env('AI_INTERNAL_TOKEN', 'change_this_internal_ai_token'),

    'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 30),

    'mock_mode' => filter_var(env('AI_MOCK_MODE', true), FILTER_VALIDATE_BOOLEAN),

    'external_provider_calls_enabled' => filter_var(env('AI_EXTERNAL_PROVIDER_CALLS', false), FILTER_VALIDATE_BOOLEAN),

    'text_generation_provider' => env('AI_TEXT_GENERATION_PROVIDER', 'gemini'),

    'gemini_text_model' => env('GEMINI_TEXT_MODEL', 'gemini-2.5-flash'),

    'gemini_api_key' => env('GEMINI_API_KEY'),

    'gemini_api_base_url' => env('GEMINI_API_BASE_URL', 'https://generativelanguage.googleapis.com'),

    'chat_provider' => env('AI_CHAT_PROVIDER', 'gemini'),

    'chat_model' => env('AI_CHAT_MODEL', env('GEMINI_TEXT_MODEL', 'gemini-2.5-flash')),

    'text_understanding_provider' => env('AI_TEXT_UNDERSTANDING_PROVIDER', 'gemini'),

    'text_understanding_model' => env('AI_TEXT_UNDERSTANDING_MODEL', env('GEMINI_TEXT_MODEL', 'gemini-2.5-flash')),

    'tryon_provider' => env('AI_TRYON_PROVIDER', 'external_tryon'),

    'tryon_model' => env('AI_TRYON_MODEL', 'virtual-tryon-v1'),

    'tryon_api_base_url' => env('AI_TRYON_API_BASE_URL'),

    'tryon_api_key' => env('AI_TRYON_API_KEY'),

    'tryon_create_endpoint' => env('AI_TRYON_CREATE_ENDPOINT', '/tryon/generate'),

    'tryon_status_endpoint' => env('AI_TRYON_STATUS_ENDPOINT', '/status/{id}'),

    'tryon_mode' => env('AI_TRYON_MODE', 'sync'),

    'tryon_output_format' => env('AI_TRYON_OUTPUT_FORMAT', 'png'),

    'tryon_resolution' => env('AI_TRYON_RESOLUTION', '1k'),

    'tryon_generation_mode' => env('AI_TRYON_GENERATION_MODE', 'balanced'),

    'tryon_return_base64' => filter_var(env('AI_TRYON_RETURN_BASE64', false), FILTER_VALIDATE_BOOLEAN),

    'tryon_prompt' => env('AI_TRYON_PROMPT', "Keep the person's identity and pose. Place the garment naturally and realistically."),

    'video_provider' => env('AI_VIDEO_PROVIDER', 'veo'),

    'video_model' => env('AI_VIDEO_MODEL', 'veo-3'),

    'veo_api_base_url' => env('VEO_API_BASE_URL', env('GEMINI_API_BASE_URL', 'https://generativelanguage.googleapis.com')),

    'veo_api_key' => env('VEO_API_KEY', env('GEMINI_API_KEY')),

    'runway_api_base_url' => env('RUNWAY_API_BASE_URL'),

    'runway_api_key' => env('RUNWAY_API_KEY'),

    'digital_twin_provider' => env('AI_DIGITAL_TWIN_PROVIDER', 'avatar_3d'),

    'digital_twin_model' => env('AI_DIGITAL_TWIN_MODEL', 'avatar-3d-multiview-v1'),

    'digital_twin_api_base_url' => env('AI_DIGITAL_TWIN_API_BASE_URL'),

    'digital_twin_api_key' => env('AI_DIGITAL_TWIN_API_KEY'),
];
