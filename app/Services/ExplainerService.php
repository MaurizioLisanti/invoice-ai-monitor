<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Genera spiegazioni in italiano plain-language dello stato semaforo.
 *
 * Produce tre sezioni separate: STATO, DIAGNOSI PROBABILE, AZIONE CONSIGLIATA.
 * La logica è rule-based (soglie numeriche pending/errori).
 *
 * [M2] Se EXPLAINER_USE_LLM=true chiama Gemini per la sezione STATO.
 *      In caso di errore API applica graceful degradation verso i
 *      template statici — nessuna eccezione propagata al controller.
 */
class ExplainerService
{
    /**
     * Restituisce una spiegazione strutturata in tre sezioni.
     *
     * @param  array{status: string, pending: int, errors: int}  $snapshot
     * @return array{stato: string, diagnosi: string, azione: string}
     */
    public function explain(array $snapshot): array
    {
        $pending = (int) ($snapshot['pending'] ?? 0);
        $errors = (int) ($snapshot['errors'] ?? 0);
        $status = (string) ($snapshot['status'] ?? '');

        if ((bool) config('invoice.explainer_use_llm', false)) {
            $llmResult = $this->explainWithLlm($snapshot);

            if ($llmResult !== null) {
                $rules = $this->ruleBasedAnalysis($pending, $errors, $status);

                return [
                    'stato' => $llmResult,
                    'diagnosi' => $rules['diagnosi'],
                    'azione' => $rules['azione'],
                ];
            }
        }

        return $this->explainWithTemplate($snapshot);
    }

    /**
     * Chiama Gemini per generare la descrizione dello stato.
     * Restituisce null su qualsiasi errore per consentire il fallback ai template statici.
     */
    private function explainWithLlm(array $snapshot): ?string
    {
        $apiKey = (string) config('ai.gemini_api_key');
        $model = (string) config('ai.model');
        $endpoint = rtrim((string) config('ai.gemini_endpoint'), '/');
        $url = "{$endpoint}/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(15)->post($url, [
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
                        'parts' => [['text' => 'Genera la spiegazione per lo stato attuale della coda fatture.']],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('explainer_llm_failed', [
                'service' => 'ExplainerService',
                'operation' => 'explainWithLlm',
                'error' => $e->getMessage(),
                'fallback' => 'template',
                'ts' => now()->toIso8601String(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('explainer_llm_failed', [
                'service' => 'ExplainerService',
                'operation' => 'explainWithLlm',
                'http_status' => $response->status(),
                'fallback' => 'template',
                'ts' => now()->toIso8601String(),
            ]);

            return null;
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            Log::warning('explainer_llm_failed', [
                'service' => 'ExplainerService',
                'operation' => 'explainWithLlm',
                'error' => 'risposta LLM malformata o vuota',
                'fallback' => 'template',
                'ts' => now()->toIso8601String(),
            ]);

            return null;
        }

        return trim($text);
    }

    private function buildSystemPrompt(array $snapshot): string
    {
        $stato = match ($snapshot['status'] ?? '') {
            'green' => 'VERDE — tutto regolare',
            'yellow' => 'GIALLO — attenzione richiesta',
            'red' => 'ROSSO — problema critico',
            default => 'SCONOSCIUTO',
        };

        $pending = (int) ($snapshot['pending'] ?? 0);
        $errors = (int) ($snapshot['errors'] ?? 0);

        return <<<PROMPT
Sei un assistente per la fatturazione elettronica di una PMI italiana.
Il tuo compito è generare una descrizione breve dello stato attuale in italiano, comprensibile per un responsabile amministrativo non tecnico.
NON usare mai i seguenti termini tecnici: HTTP, SQL, exception, stack trace, PDO, query, database, cache, Laravel, API.
Contesto attuale della coda fatture:
- Stato semaforo: {$stato}
- Fatture in attesa di invio: {$pending}
- Fatture in errore: {$errors}
Rispondi in italiano con una frase breve e diretta (max 2 righe) che descrive lo stato attuale.
PROMPT;
    }

    /**
     * @return array{stato: string, diagnosi: string, azione: string}
     */
    private function explainWithTemplate(array $snapshot): array
    {
        $pending = (int) ($snapshot['pending'] ?? 0);
        $errors = (int) ($snapshot['errors'] ?? 0);
        $status = (string) ($snapshot['status'] ?? '');

        if (! in_array($status, ['green', 'yellow', 'red'], true)) {
            Log::warning('explain_unknown_status', [
                'service' => 'ExplainerService',
                'operation' => 'explain',
                'status' => $status,
                'ts' => now()->toIso8601String(),
            ]);
        }

        return $this->ruleBasedAnalysis($pending, $errors, $status);
    }

    /**
     * Logica rule-based per le tre sezioni.
     *
     * Regole primarie (soglie numeriche):
     *   • pending > 500 AND errori > 10  → critico
     *   • pending > 50  AND errori > 3   → attenzione
     *   • pending < 10  AND errori = 0   → tutto ok
     * Fallback: usa lo stato semaforo per i casi non coperti dalle regole.
     *
     * @return array{stato: string, diagnosi: string, azione: string}
     */
    private function ruleBasedAnalysis(int $pending, int $errors, string $status): array
    {
        if ($pending > 500 && $errors > 10) {
            return [
                'stato' => "Critico — {$pending} fatture in attesa, {$errors} in errore",
                'diagnosi' => 'Backlog anomalo sul flusso di invio; possibile rallentamento worker o errore di integrazione SDI.',
                'azione' => 'Verificare worker attivi, ultime eccezioni, coda di invio e fatture ferme oltre soglia.',
            ];
        }

        if ($pending > 50 && $errors > 3) {
            return [
                'stato' => "Attenzione — {$pending} fatture in attesa, {$errors} in errore",
                'diagnosi' => 'Accumulo di fatture in coda superiore alla norma; possibile rallentamento del flusso SDI.',
                'azione' => 'Monitorare la situazione; se non migliora entro 30 minuti, avvisare il supporto tecnico.',
            ];
        }

        if ($pending < 10 && $errors === 0) {
            return [
                'stato' => "Tutto OK — {$pending} fatture in attesa, nessun errore",
                'diagnosi' => 'Il flusso di fatturazione è regolare e nei limiti normali.',
                'azione' => 'Nessuna azione richiesta.',
            ];
        }

        // Fallback basato sullo stato semaforo per i casi non coperti
        $detail = $this->buildDetail($pending, $errors);

        return match ($status) {
            'red' => [
                'stato' => "Critico — {$detail}",
                'diagnosi' => 'Anomalia rilevata nel flusso di fatturazione; verifica necessaria.',
                'azione' => 'Contattare il supporto tecnico e verificare i log di errore.',
            ],
            'yellow' => [
                'stato' => "Attenzione — {$detail}",
                'diagnosi' => 'La coda di fatturazione mostra segnali di rallentamento.',
                'azione' => "Monitorare l'andamento nelle prossime ore; se la situazione peggiora, avvisare il supporto tecnico.",
            ],
            default => [
                'stato' => "Tutto OK — {$detail}",
                'diagnosi' => 'Il flusso di fatturazione è nei limiti normali.',
                'azione' => 'Nessuna azione richiesta.',
            ],
        };
    }

    private function buildDetail(int $pending, int $errors): string
    {
        $parts = [];

        if ($pending > 0) {
            $parts[] = "{$pending} fatture in attesa";
        }

        if ($errors > 0) {
            $parts[] = "{$errors} in errore";
        }

        return implode(', ', $parts) ?: 'coda regolare';
    }
}
