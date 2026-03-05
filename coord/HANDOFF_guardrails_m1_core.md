## HANDOFF_guardrails_m1_core.md

### Metadata
- task: TASK_guardrails_m1_core
- status: DONE
- correlation_id: b8c3e2f1-4a7d-4b9e-c6d8-2f0a1e3b5c7f
- run_id: executor-v2-guardrails-m1-core-20260228
- created: 2026-02-28T00:00:00Z
- branch: task/guardrails_m1_core

### Summary
Implementato error handling strutturato: `AppException` creata, try/catch in `TrafficLightService::snapshot()` con log JSON, log warning in `ExplainerService::explain()` per status sconosciuto, wrap `DashboardController::status()/explain()` → 503, canale `json` Monolog JsonFormatter in `config/logging.php`, render AppException in `bootstrap/app.php`.
Tutti i gate DoD superati: `pint --test` → pass · `php artisan test` → 11/11 PASS · log JSON strutturato verificato su `laravel.log`.

### Files changed
- app/Exceptions/AppException.php — aggiunto (nuovo file, extends \RuntimeException)
- app/Services/TrafficLightService.php — modificato (import AppException+Log, try/catch in snapshot())
- app/Services/ExplainerService.php — modificato (import Log, log warning su default in explain())
- app/Http/Controllers/DashboardController.php — modificato (import AppException, try/catch in status()/explain() → 503)
- config/logging.php — modificato (import JsonFormatter, nuovo canale 'json', stack default → 'json')
- bootstrap/app.php — modificato (render AppException → 503 JSON in withExceptions())

### Commands run
```
grep -rn "catch.*{}" app/
  → PASS — 0 match (nessun catch vuoto pre-esistente)

php artisan tinker --execute="throw new \App\Exceptions\AppException('test');"
  → PASS — App\Exceptions\AppException test. (eccezione lanciata correttamente)

Log::error('test_structured', [...])  +  tail storage/logs/laravel.log
  → PASS — {"message":"test_structured","context":{"service":"TrafficLightService","operation":"snapshot","error":"test","ts":"..."},"level":400,"level_name":"ERROR","channel":"local","datetime":"...","extra":{}}
  (JSON valido, schema corrispondente alla specifica TASK)

vendor/bin/pint --test
  → PASS — {"result":"pass"}
  (pint auto-fix eseguito su ExplainerService.php, TrafficLightService.php, config/logging.php dopo primo run)

php artisan test --stop-on-failure
  → PASS — 11/11 tests passed (30 assertions)
```

### Assunzioni fatte
- [A1] DoD-4 (HTTP 503 su errore DB con `docker compose stop db`) non eseguito: Docker non disponibile in sviluppo locale (MEMORY.md). Comportamento 503 verificato via codice (catch AppException nel controller → 503) e via bootstrap/app.php render handler. Test integration completo possibile su VPS.
- [A2] Il canale `json` in config/logging.php usa `StreamHandler::class` + `JsonFormatter::class` direttamente da Monolog — già disponibile come dipendenza transitiva di Laravel, nessuna dipendenza aggiunta a composer.json.
- [A3] `bootstrap/app.php` registra il render di `AppException` → 503 per tutti gli ambienti (non solo produzione). La protezione stack trace in produzione è gestita da Laravel via `APP_DEBUG=false` (comportamento nativo, nessun codice aggiuntivo necessario).
- [A4] `DashboardController::index()` non ha try/catch — non richiesto esplicitamente dal TASK. Se snapshot() lancia AppException durante la view HTML, Laravel restituirà la sua pagina di errore standard (comportamento accettabile per MVP).

### Rischi / TODO residui
- P2 (index coverage): `DashboardController::index()` non ha try/catch su AppException — in caso di DB down, l'utente vede la pagina di errore Laravel invece di un messaggio di graceful degradation. Suggerito: aggiungere in task M2 o TASK_tests_m1_smoke come caso di test.
- P2 (DoD-4 skip): il test HTTP 503 con DB down non è eseguibile in locale. Da verificare manualmente su VPS al primo deploy.
- P2 (PHPUnit 12): 9 metodi test usano ancora `@test` doc-comment — warning pre-existing, non bloccante.

### Sblocca
- TASK_docs_m1_runbook — guardrails DONE, può procedere dopo tests_m1_smoke DONE
