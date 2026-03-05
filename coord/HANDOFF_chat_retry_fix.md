## HANDOFF_chat_retry_fix.md

### Metadata
- task: chat_retry_fix (hotfix post-M2)
- status: DONE
- correlation_id: a1f3c7e2-4d9b-4a6e-b0c8-2f5d8e3a1c7f
- run_id: executor-v2-chat-retry-fix-20260303
- created: 2026-03-03T00:00:00Z

### Contesto
La chat AI restituiva 503 a ogni richiesta. Causa: `AI_MODEL=gemini-1.5-flash`
era deprecato, corretto in `gemini-2.0-flash`. Con il nuovo modello emergeva
un 429 (rate limit piano free Gemini). `ChatService` propagava tutti gli errori
HTTP come `AppException` → `ChatController` rispondeva sempre 503.

### Summary
Aggiunti retry con backoff e fallback silenzioso al template M0 in `ChatService`:

- **Max 3 tentativi** sulla stessa richiesta in caso di 429
- **Attesa configurabile** tra tentativi (`AI_RETRY_DELAY`, default 2s, 0 in test)
- **Fallback template M0** se tutti e 3 i tentativi falliscono: testo contestuale
  basato su `snapshot['status']` (green/yellow/red), stesso registro linguistico
  dell'`ExplainerService` — l'utente non vede mai "servizio non disponibile"
- **Errori non-429** (404, 500, timeout, rete) propagano ancora `AppException`
  → `ChatController` restituisce 503 (comportamento M2 invariato)

### Decisioni architetturali
- `waitBeforeRetry()` è `protected` (non `private`) per testabilità futura
  via partial mock; nella pratica il ritardo è pilotato da `AI_RETRY_DELAY=0`
  in `phpunit.xml`, senza modificare il service per i test
- Il fallback vive in `ChatService::chatFallback()` (privato): non riusa
  `ExplainerService` per evitare dipendenza cross-service; il testo è allineato
  ai template M0 ma scritto in registro "risposta chat" (prima persona implicita)
- `MAX_ATTEMPTS = 3` è una costante di classe tipizzata (PHP 8.4)

### Files changed
- `app/Services/ChatService.php` — refactor: retry loop, `waitBeforeRetry()`,
  `callApi()`, `buildPayload()` estratti, aggiunto `chatFallback()`
- `config/ai.php` — aggiunta chiave `retry_delay_seconds` (env `AI_RETRY_DELAY`)
- `phpunit.xml` — aggiunto `<env name="AI_RETRY_DELAY" value="0"/>` (no sleep in test)
- `tests/Feature/ChatTest.php` — aggiunto `use Http`, 3 nuovi test:
  - `chat_service_ritorna_fallback_template_dopo_tre_429`
  - `chat_service_riprova_dopo_429_e_restituisce_risposta_ai`
  - `chat_controller_ritorna_200_con_fallback_dopo_tre_429`
- `.env` — `AI_MODEL` aggiornato da `gemini-1.5-flash` a `gemini-2.0-flash`

### Test results
```
php artisan test --stop-on-failure
  → PASS — 37/37 tests passed (88 assertions) in 18.58s
  (3 nuovi test ChatTest, 34 pre-esistenti tutti PASS, 0 regressioni)

vendor/bin/pint app/Services/ChatService.php tests/Feature/ChatTest.php config/ai.php --test
  → {"result":"pass"}
```

### Comportamento utente finale
| Scenario                        | Prima del fix | Dopo il fix              |
|---------------------------------|---------------|--------------------------|
| 429 al primo tentativo          | 503           | retry → risposta AI      |
| 429 × 3 consecutivi             | 503           | 200 + template M0        |
| Errore 404/500/timeout          | 503           | 503 (invariato)          |
| Risposta AI corretta            | 200           | 200 (invariato)          |

### Note operative
- Piano free Gemini: ~15 RPM. Se 429 persistono oltre la finestra di retry,
  il fallback template copre l'utente senza interruzioni visibili.
- Per aumentare la quota: attivare billing su Google AI Studio con la stessa chiave.
- `AI_RETRY_DELAY` è modificabile in `.env` senza deploy (default 2s).
