<?php

return [

    'default' => env('AI_PROVIDER', 'gemini'),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'thinking_level' => env('GEMINI_THINKING_LEVEL', 'low'),
        'connect_timeout' => 5,
        'timeout' => (int) env('GEMINI_TIMEOUT', 60),
        'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 8192),
        'retry_backoff_ms' => [2000, 8000],
    ],

    'experiment_log' => storage_path('logs/ai-experiments.jsonl'),

];
