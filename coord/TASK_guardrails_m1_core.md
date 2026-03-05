# TASK_guardrails_m1_core

## Metadata
- **created**: 2026-02-23T00:00:00Z
- **updated**: 2026-02-23T00:00:00Z  ← completato da Planner Agent v3
- **assignee**: Claude
- **status**: BLOCKED
- **milestone**: M1
- **risk tier**: MED

---

## Obiettivo
Implementare error handling strutturato e logging JSON: `AppException`, handler Laravel che non espone stack trace in produzione, try/catch nei service con log contestuali, risposta HTTP 503 su errori di infrastruttura. Al termine: nessun `catch {}` vuoto nel codebase, log in formato JSON valido.

> ⚠️ Questo task può essere eseguito **in parallelo** con `TASK_tests_m1_smoke` — i path sono disgiunti.

---

## Scope
- [ ] Creare `app/Exceptions/AppException.php` extends `\RuntimeException`
- [ ] Laravel 11 exception handler (`bootstrap/app.php`): in produzione (`APP_ENV=production`) non esporre stack trace nei JSON error response
- [ ] `DashboardController::status()` e `explain()`: catch `AppException` → `response()->json(['error' => 'Servizio temporaneamente non disponibile'], 503)`
- [ ] `TrafficLightService::snapshot()`: try/catch `\Throwable` → `Log::error(...)` + throw `AppException`
- [ ] `ExplainerService::explain()`: gestione `default` nel match già presente — aggiungere log warning
- [ ] `config/logging.php`: canale `stack` configurato con formatter JSON (Monolog `JsonFormatter` o logging personalizzato)
- [ ] Ogni chiamata `Log::*` usa array strutturato: `['service' => ..., 'operation' => ..., 'error' => ..., 'ts' => now()->toIso8601String()]`
- [ ] Audit: verificare che nessun file nel codebase abbia `catch (\Throwable $e) {}` vuoto o `catch (\Exception $e) { // ignore }`
- [ ] Verifica: `storage/logs/laravel.log` produce JSON valido (non plain text)

## Non-scope
- NON implementare autenticazione o middleware di sicurezza [A2]
- NON aggiungere logging di eventi di business (es. "fattura X inviata con successo")
- NON implementare alert/notifiche esterne (email, Slack)
- NON modificare il DB schema o i seeder
- NON toccare `resources/views/` o CSS

---

## Allowed paths
```
app/Exceptions/AppException.php      ← nuovo file
app/Http/Controllers/DashboardController.php
app/Services/TrafficLightService.php
app/Services/ExplainerService.php
config/logging.php
bootstrap/app.php                    ← solo sezione exception handler
```

## Forbidden paths
```
database/
resources/views/
routes/
tests/                               ← no modifica test qui
Dockerfile
docker-compose.yml
coord/
app/Models/
```

---

## Dipendenze
- **BLOCKED_BY**: TASK_scaffold_m0_boot
- **BLOCKS**: TASK_docs_m1_runbook
- **PARALLEL con**: TASK_tests_m1_smoke (path disgiunti — sicuro)
- **Pre-check**: TASK_scaffold_m0_boot → status DONE? **NO** → stato BLOCKED

---

## Definition of Done

```bash
# 1. AppException esiste
docker compose exec app php artisan tinker \
  --execute="throw new \App\Exceptions\AppException('test');"
# → PASS se l'eccezione viene lanciata (non fatal error PHP)

# 2. Nessun catch vuoto
docker compose exec app grep -rn "catch.*{}" app/
# → PASS se output vuoto (0 match)

# 3. Log JSON strutturato (simulare errore DB)
docker compose exec app php artisan tinker \
  --execute="\Illuminate\Support\Facades\Log::error('test_structured', ['service'=>'test','ts'=>now()->toIso8601String()]);"
docker compose exec app tail -n 3 storage/logs/laravel.log
# → PASS se output è JSON valido (contiene '{' e '}')

# 4. HTTP 503 su errore DB (test manuale)
docker compose stop db
curl -s http://localhost:8080/status
# → PASS se risposta è JSON {"error":"Servizio temporaneamente non disponibile"} con status 503
docker compose start db   # ripristina

# 5. Test suite (no regressioni)
docker compose exec app php artisan test --stop-on-failure
# → PASS

# 6. Linter
docker compose exec app vendor/bin/pint --test
# → PASS

# 7. HANDOFF prodotto
ls coord/HANDOFF_guardrails_m1_core.md
# → PASS se esiste con correlation_id
```

---

## Comandi verifica rapida
```bash
# Verifica formato log attivo
docker compose exec app php artisan tinker \
  --execute="\Log::warning('guardrails_check', ['ok'=>true,'ts'=>now()->toIso8601String()]);"
cat storage/logs/laravel.log | python3 -c "import sys,json; [json.loads(l) for l in sys.stdin if l.strip()]"
# → PASS se nessun errore JSON parse
```

---

## Schema log atteso (formato JSON)
```json
{
  "message": "snapshot_failed",
  "context": {
    "service": "TrafficLightService",
    "operation": "snapshot",
    "error": "SQLSTATE[HY000]: ...",
    "ts": "2026-02-23T09:00:00+01:00"
  },
  "level": 400,
  "level_name": "ERROR",
  "channel": "local",
  "datetime": "2026-02-23T09:00:00.000000+01:00"
}
```

---

## Template HANDOFF da produrre

Creare `coord/HANDOFF_guardrails_m1_core.md` con:
```
status: DONE
correlation_id: <uuid-v4>   ← OBBLIGATORIO
branch: task/guardrails_m1_core
```
