<?php

/**
 * Configurazione invoice-ai-monitor.
 * Tutti i valori sono sovrascrivibili via .env.
 */
return [

    // ── Tabella sorgente [A1] [A5] ────────────────────────────────
    'queue_table' => env('INVOICE_QUEUE_TABLE', 'invoices_queue'),

    // ── Soglie semaforo ───────────────────────────────────────────
    'semaforo_yellow_pending' => (int) env('SEMAFORO_YELLOW_PENDING', 10),
    'semaforo_yellow_errors' => (int) env('SEMAFORO_YELLOW_ERRORS', 3),
    'semaforo_red_pending' => (int) env('SEMAFORO_RED_PENDING', 50),
    'semaforo_red_errors' => (int) env('SEMAFORO_RED_ERRORS', 10),

    // ── Dashboard ─────────────────────────────────────────────────
    'dashboard_refresh_seconds' => (int) env('DASHBOARD_REFRESH_SECONDS', 60),

    // ── Cache ─────────────────────────────────────────────────────
    'cache_ttl_seconds' => (int) env('CACHE_TTL_SECONDS', 30),

    // ── Spiegami LLM [M2] ─────────────────────────────────────────
    // Se true: ExplainerService chiama Gemini per spiegazioni contestuali.
    // Se false (o se l'API non risponde): fallback ai template statici M0.
    'explainer_use_llm' => (bool) env('EXPLAINER_USE_LLM', false),

];
