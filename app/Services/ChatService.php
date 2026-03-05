<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatService
{
    private const int MAX_ATTEMPTS = 3;

    /**
     * Pone una domanda contestualizzata all'AI e restituisce la risposta.
     *
     * Il provider è selezionato da config('ai.provider'):
     *   - 'gemini' (default): retry su 429, AppException su altri errori
     *   - 'openrouter': retry su 429, fallback template M0 su qualsiasi errore
     *
     * @param  array  $snapshot  Output di TrafficLightService::snapshot()
     * @param  string  $question  Domanda dell'utente (già validata, max 500 caratteri)
     * @return string Risposta testuale in italiano
     *
     * @throws AppException solo con provider Gemini su errori non-429
     */
    public function ask(array $snapshot, string $question): string
    {
        return match ((string) config('ai.provider', 'gemini')) {
            'openrouter' => $this->askOpenRouter($snapshot, $question),
            default => $this->askGemini($snapshot, $question),
        };
    }

    // ── Gemini ───────────────────────────────────────────────────────

    /**
     * Chiama Gemini con retry su 429 e fallback template dopo MAX_ATTEMPTS.
     * Lancia AppException su errori HTTP non-429 o timeout.
     */
    private function askGemini(array $snapshot, string $question): string
    {
        $payload = $this->buildGeminiPayload($snapshot, $question);

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = $this->callGemini($payload);
            } catch (\Throwable $e) {
                Log::error('chat_api_error', [
                    'service' => 'ChatService',
                    'provider' => 'gemini',
                    'operation' => 'ask',
                    'error' => $e->getMessage(),
                    'ts' => now()->toISOString(),
                ]);

                throw new AppException('Timeout o errore di rete verso il servizio AI.', 0, $e);
            }

            if ($response->status() === 429) {
                Log::warning('chat_api_ratelimit', [
                    'service' => 'ChatService',
                    'provider' => 'gemini',
                    'operation' => 'ask',
                    'attempt' => $attempt,
                    'ts' => now()->toISOString(),
                ]);

                if ($attempt < self::MAX_ATTEMPTS) {
                    $this->waitBeforeRetry();
                }

                continue;
            }

            if (! $response->successful()) {
                Log::error('chat_api_error', [
                    'service' => 'ChatService',
                    'provider' => 'gemini',
                    'operation' => 'ask',
                    'http_status' => $response->status(),
                    'ts' => now()->toISOString(),
                ]);

                throw new AppException('Il servizio AI ha restituito un errore HTTP '.$response->status().'.');
            }

            $text = $response->json('candidates.0.content.parts.0.text');

            if (! is_string($text) || $text === '') {
                Log::error('chat_api_error', [
                    'service' => 'ChatService',
                    'provider' => 'gemini',
                    'operation' => 'ask',
                    'error' => 'risposta AI malformata o vuota',
                    'ts' => now()->toISOString(),
                ]);

                throw new AppException('Risposta AI malformata o vuota.');
            }

            return $text;
        }

        Log::warning('chat_ratelimit_fallback', [
            'service' => 'ChatService',
            'provider' => 'gemini',
            'operation' => 'ask',
            'attempts' => self::MAX_ATTEMPTS,
            'fallback' => 'template',
            'ts' => now()->toISOString(),
        ]);

        return $this->chatFallback($snapshot);
    }

    // ── OpenRouter ───────────────────────────────────────────────────

    /**
     * Chiama OpenRouter (API OpenAI-compatibile) con retry su 429.
     * Qualsiasi errore (timeout, HTTP non-429, risposta malformata) produce
     * un fallback silenzioso al template M0 — non viene mai lanciata AppException.
     */
    private function askOpenRouter(array $snapshot, string $question): string
    {
        $payload = $this->buildOpenRouterPayload($snapshot, $question);

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = $this->callOpenRouter($payload);
            } catch (\Throwable $e) {
                Log::warning('chat_api_error', [
                    'service' => 'ChatService',
                    'provider' => 'openrouter',
                    'operation' => 'ask',
                    'error' => $e->getMessage(),
                    'fallback' => 'template',
                    'ts' => now()->toISOString(),
                ]);

                return $this->chatFallback($snapshot);
            }

            if ($response->status() === 429) {
                Log::warning('chat_api_ratelimit', [
                    'service' => 'ChatService',
                    'provider' => 'openrouter',
                    'operation' => 'ask',
                    'attempt' => $attempt,
                    'ts' => now()->toISOString(),
                ]);

                if ($attempt < self::MAX_ATTEMPTS) {
                    $this->waitBeforeRetry();
                }

                continue;
            }

            if (! $response->successful()) {
                Log::warning('chat_api_error', [
                    'service' => 'ChatService',
                    'provider' => 'openrouter',
                    'operation' => 'ask',
                    'http_status' => $response->status(),
                    'fallback' => 'template',
                    'ts' => now()->toISOString(),
                ]);

                return $this->chatFallback($snapshot);
            }

            $text = $response->json('choices.0.message.content');

            if (! is_string($text) || $text === '') {
                Log::warning('chat_api_error', [
                    'service' => 'ChatService',
                    'provider' => 'openrouter',
                    'operation' => 'ask',
                    'error' => 'risposta AI malformata o vuota',
                    'fallback' => 'template',
                    'ts' => now()->toISOString(),
                ]);

                return $this->chatFallback($snapshot);
            }

            return trim($text);
        }

        Log::warning('chat_ratelimit_fallback', [
            'service' => 'ChatService',
            'provider' => 'openrouter',
            'operation' => 'ask',
            'attempts' => self::MAX_ATTEMPTS,
            'fallback' => 'template',
            'ts' => now()->toISOString(),
        ]);

        return $this->chatFallback($snapshot);
    }

    // ── HTTP calls ───────────────────────────────────────────────────

    protected function waitBeforeRetry(): void
    {
        sleep((int) config('ai.retry_delay_seconds', 2));
    }

    private function callGemini(array $payload): Response
    {
        $apiKey = (string) config('ai.gemini_api_key');
        $model = (string) config('ai.model');
        $endpoint = rtrim((string) config('ai.gemini_endpoint'), '/');
        $url = "{$endpoint}/{$model}:generateContent?key={$apiKey}";

        return Http::timeout(15)->post($url, $payload);
    }

    private function callOpenRouter(array $payload): Response
    {
        $apiKey = (string) config('ai.openrouter_api_key');
        $url = (string) config('ai.openrouter_endpoint');

        return Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'HTTP-Referer' => (string) config('app.url', 'http://localhost'),
        ])->timeout(15)->post($url, $payload);
    }

    // ── Payload builders ─────────────────────────────────────────────

    private function buildGeminiPayload(array $snapshot, string $question): array
    {
        return [
            'systemInstruction' => [
                'parts' => [['text' => $this->buildSystemPrompt($snapshot)]],
            ],
            'generationConfig' => [
                'maxOutputTokens' => config('ai.max_tokens'),
                'temperature' => config('ai.temperature'),
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $question]],
                ],
            ],
        ];
    }

    private function buildOpenRouterPayload(array $snapshot, string $question): array
    {
        return [
            'model' => (string) config('ai.openrouter_model'),
            'messages' => [
                ['role' => 'system', 'content' => $this->buildSystemPrompt($snapshot)],
                ['role' => 'user',   'content' => $question],
            ],
            'max_tokens' => (int) config('ai.max_tokens'),
            'temperature' => (float) config('ai.temperature'),
        ];
    }

    // ── Shared ───────────────────────────────────────────────────────

    private function buildSystemPrompt(array $snapshot): string
    {
        $stato = $snapshot['status'] ?? 'unknown';
        $pending = (int) ($snapshot['pending'] ?? 0);
        $errors = (int) ($snapshot['errors'] ?? 0);

        $statoLabel = match ($stato) {
            'green'  => 'VERDE',
            'yellow' => 'GIALLO',
            'red'    => 'ROSSO',
            default  => 'SCONOSCIUTO',
        };

        return <<<PROMPT
Sei un assistente esperto per la gestione della coda SDI (fatturazione elettronica) di una PMI italiana.

## STATO ATTUALE DELLA CODA
- Semaforo: {$statoLabel}
- Fatture in attesa (pending): {$pending}
- Fatture in errore: {$errors}

## SOGLIE DI ALLERTA
- VERDE  → pending ≤ 10 E errori = 0        (tutto nella norma)
- GIALLO → pending > 10 OPPURE errori > 3   (attenzione necessaria)
- ROSSO  → pending > 50 OPPURE errori > 10  (problema critico)

## ISTRUZIONI OBBLIGATORIE
1. Rispondi SEMPRE in italiano, in modo diretto e senza giri di parole.
2. Valuta la gravità basandoti sui numeri esatti e sulle soglie sopra indicate — non fidarti solo dell'etichetta semaforo.
3. Se pending > 50 o errori > 10, la situazione È critica: dillo esplicitamente anche se l'etichetta dice altro.
4. Non minimizzare mai una situazione grave. Se i numeri indicano un problema, segnalalo chiaramente.
5. Rispondi in massimo 3-4 frasi, senza termini tecnici complessi.
PROMPT;
    }

    private function chatFallback(array $snapshot): string
    {
        $status = $snapshot['status'] ?? 'unknown';
        $pending = (int) ($snapshot['pending'] ?? 0);
        $errors = (int) ($snapshot['errors'] ?? 0);

        return match ($status) {
            'green' => "Tutto funziona correttamente. Ci sono {$pending} fatture in attesa di invio e {$errors} in errore: valori nei limiti normali. Nessuna azione richiesta.",
            'yellow' => "Attenzione: ci sono {$pending} fatture in attesa e {$errors} in errore. Il sistema è operativo ma richiede monitoraggio. Se la situazione non migliora entro 30 minuti, avvisa il supporto tecnico.",
            'red' => "PROBLEMA CRITICO: {$pending} fatture bloccate in attesa e {$errors} in errore. Il sistema di fatturazione potrebbe essere bloccato. Contatta immediatamente il supporto tecnico.",
            default => 'Stato non riconosciuto. Contatta il supporto tecnico.',
        };
    }
}
