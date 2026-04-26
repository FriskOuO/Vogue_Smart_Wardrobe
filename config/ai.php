<?php

return [
    'service_url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8001'),

    'internal_token' => env('AI_INTERNAL_TOKEN', 'change_this_internal_ai_token'),

    'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 30),

    'mock_mode' => filter_var(env('AI_MOCK_MODE', true), FILTER_VALIDATE_BOOLEAN),
];
