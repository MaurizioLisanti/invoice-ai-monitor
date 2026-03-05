<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI — provider + parametri condivisi [M2 / M3]
    |--------------------------------------------------------------------------
    | AI_PROVIDER=gemini (default) → Gemini REST (GEMINI_API_KEY)
    | AI_PROVIDER=openrouter       → OpenRouter OpenAI-compat (OPENROUTER_API_KEY)
    */

    // Provider attivo: 'gemini' | 'openrouter'
    'provider' => env('AI_PROVIDER', 'gemini'),

    // ── Gemini ──────────────────────────────────────────────────────
    'gemini_api_key' => env('GEMINI_API_KEY', ''),
    'gemini_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
    'model' => env('AI_MODEL', 'gemini-2.0-flash'),

    // ── OpenRouter ──────────────────────────────────────────────────
    'openrouter_api_key' => env('OPENROUTER_API_KEY', ''),
    'openrouter_endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
    'openrouter_model' => env('OPENROUTER_MODEL', 'meta-llama/llama-3.1-8b-instruct:free'),

    // ── Parametri condivisi ──────────────────────────────────────────
    'max_tokens' => (int) env('AI_MAX_TOKENS', 512),
    'temperature' => (float) env('AI_TEMPERATURE', 0.3),
    'retry_delay_seconds' => (int) env('AI_RETRY_DELAY', 2),
];
