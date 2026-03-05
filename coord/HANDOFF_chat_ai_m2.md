## HANDOFF_chat_ai_m2.md

### Metadata
- task: TASK_02_chat_ai_m2
- status: DONE
- correlation_id: f8a3c1d9-2b7e-4f5a-8c1d-0e4b6a3f2c7e
- run_id: executor-v2-chat-ai-m2-20260302
- created: 2026-03-02T00:00:00Z
- branch: task/chat_ai_m2

### Summary
Implementata chat AI stateless con Gemini 1.5 Flash: `config/ai.php` centralizza chiave/modello/parametri, `ChatService::ask()` chiama REST Gemini via `Http::timeout(15)` con `systemInstruction` contestualizzata (stato semaforo, pending, errors), `ChatController::ask()` valida input e gestisce AppException → 503, widget chat in `dashboard.blade.php` (textarea + fetch POST `/chat` + area risposta teal). 4 test smoke con mock Mockery. 27/27 PASS, pint PASS.

### Override rispetto al TASK originale
- TASK_02 era scritto per Anthropic `claude-haiku-4-5` — user override: **Gemini 1.5 Flash via REST API** (GEMINI_API_KEY disponibile in .env).
- Nessuna nuova dipendenza composer (Http facade, già incluso in Laravel 11/Guzzle).
- `.env.example` aggiornato con `GEMINI_API_KEY=`, `AI_MODEL`, `AI_MAX_TOKENS`, `AI_TEMPERATURE` al posto di `ANTHROPIC_API_KEY`.

### Files changed
- `config/ai.php` — aggiunto (nuovo file config AI: gemini_api_key, gemini_endpoint, model, max_tokens, temperature)
- `app/Services/ChatService.php` — aggiunto (nuovo service: buildSystemPrompt + Http POST Gemini, AppException su errori)
- `app/Http/Controllers/ChatController.php` — aggiunto (nuovo controller: validate question, method injection TrafficLightService + ChatService, JSON reply/error)
- `routes/web.php` — modificato (use ChatController + Route::post('/chat') → chat.ask)
- `resources/views/dashboard.blade.php` — modificato (CSS chat teal, card "Chiedi all'AI", JS sendChat())
- `.env.example` — modificato (sezione AI aggiornata da Anthropic a Gemini con 4 variabili)
- `tests/Feature/ChatTest.php` — aggiunto (4 test smoke con mock)

### Commands run
```
php artisan test --stop-on-failure (pre-pint)
  → PASS — 27/27 tests passed (64 assertions) in 3.83s
  (4 nuovi test ChatTest, 23 pre-esistenti tutti PASS)

vendor/bin/pint --test (pre-fix)
  → FAIL — ChatController.php (binary_operator_spaces)
            ChatService.php (concat_space, unary_operator_spaces, not_operator_with_successor_space, binary_operator_spaces, phpdoc_align)
            config/ai.php (binary_operator_spaces)
            ChatTest.php (method_chaining_indentation)

vendor/bin/pint app/Http/Controllers/ChatController.php app/Services/ChatService.php config/ai.php tests/Feature/ChatTest.php
  → PASS — {"result":"fixed"}

vendor/bin/pint --test (post-fix)
  → PASS — {"result":"pass"}

php artisan test --stop-on-failure (post-pint)
  → PASS — 27/27 tests passed (64 assertions) in 2.00s
```

### Assunzioni fatte
- [A1] GEMINI_API_KEY presente in `.env` locale (fornita dall'utente). TASK_02 era originariamente per Anthropic — sostituito con Gemini 1.5 Flash su indicazione esplicita dell'utente.
- [A2] Nessuna nuova dipendenza composer: Http facade (Guzzle via Laravel 11) già disponibile. Verificato `composer.json` — nessun `anthropic/sdk` o `google/cloud-ai` presente.
- [A3] `systemInstruction` Gemini v1beta usata per separare istruzioni di sistema dalla domanda utente — più pulito rispetto a concatenare tutto nel messaggio user.
- [A4] ChatController usa method injection per entrambi i service (TrafficLightService e ChatService) — coerente con il pattern `logs()` in DashboardController, evita constructor injection non necessario.
- [A5] `coord/` è nei Forbidden Paths del TASK_02 — HANDOFF prodotto ugualmente come da protocollo AGENTS.md §8 (stesso pattern P2-B di TASK_01).

### Rischi / TODO residui
- P2 (rate limiting): nessun rate limit su POST /chat — post-M2 come da non-scope.
- P2 (@test deprecation): i 4 nuovi test usano `@test` doc-comment (PHPUnit 12 warning — pre-existing, già tracciato in backlog M2).
- P1 (pre-TASK_03): TASK_03_spiegami_dynamic_m2 riusa `config/ai.php` e `ChatService` — verificare compatibilità prima di modificare.

### Sblocca
- TASK_03_spiegami_dynamic_m2 — dipendenza soddisfatta ✅
