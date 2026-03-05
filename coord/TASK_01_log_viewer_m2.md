# TASK_01_log_viewer_m2

## Metadata
- **created**: 2026-03-02T00:00:00Z
- **updated**: 2026-03-02T00:00:00Z
- **assignee**: Claude
- **status**: DONE ✅
- **milestone**: M2
- **risk tier**: LOW

---

## Obiettivo
Aggiungere una sezione "Log recenti" nella dashboard che mostra in tabella le ultime 50 righe del log JSON (`storage/logs/laravel.log`). L'amministrativo vede messaggi, livello e timestamp senza accedere al server via SSH.

---

## Scope
- [ ] Creare `app/Services/LogReaderService.php` — legge `storage/logs/laravel.log`, parsa ogni riga JSON, restituisce array `[message, level_name, datetime, context]` per le ultime N righe
- [ ] Aggiungere route `GET /logs` in `routes/web.php` → `DashboardController@logs`
- [ ] `DashboardController::logs()`: recupera ultimi 50 log via `LogReaderService`, passa alla view; gestione AppException → HTTP 503
- [ ] Creare view `resources/views/logs.blade.php` con tabella: colonne `Livello`, `Messaggio`, `Data/ora` — badge colore per livello (ERROR=rosso, WARNING=arancio, INFO=grigio)
- [ ] Gestione file mancante o vuoto: messaggio "Nessun log disponibile"
- [ ] Aggiungere link "Log" nella navbar/header della dashboard principale
- [ ] Pint PASS, test suite PASS (no regressioni), HANDOFF prodotto

## Non-scope
- NON implementare paginazione (50 righe fisse)
- NON implementare filtri per livello o testo
- NON esporre log via API JSON
- NON modificare la logica del semaforo o dei service esistenti

---

## Allowed paths
```
app/Services/LogReaderService.php        ← nuovo file
app/Http/Controllers/DashboardController.php
resources/views/dashboard.blade.php      ← solo aggiunta link nav
resources/views/logs.blade.php           ← nuovo file
routes/web.php
tests/Feature/LogViewerTest.php          ← nuovo file test smoke
```

## Forbidden paths
```
app/Services/TrafficLightService.php
app/Services/ExplainerService.php
app/Exceptions/
database/
config/
bootstrap/
Dockerfile
docker-compose.yml
coord/
```

---

## Dipendenze
- **BLOCKED_BY**: Wave M1 DONE ✅
- **BLOCKS**: TASK_02_chat_ai_m2
- **PARALLEL con**: nessuno (primo task M2, sblocca la catena)

---

## Definition of Done

```bash
# 1. Route log raggiungibile
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/logs
# → PASS se HTTP 200

# 2. LogReaderService gestisce file vuoto/mancante senza eccezioni
php artisan tinker --execute="app(\App\Services\LogReaderService::class)->tail(50);"
# → PASS: ritorna array (eventualmente vuoto), nessuna eccezione

# 3. Test suite no regressioni
php artisan test --stop-on-failure
# → PASS (≥ 19 test, 0 failure)

# 4. Linter
vendor/bin/pint --test
# → PASS {"result":"pass"}

# 5. HANDOFF prodotto
ls coord/HANDOFF_log_viewer_m2.md
# → PASS se esiste con correlation_id
```

---

## Template HANDOFF da produrre

Creare `coord/HANDOFF_log_viewer_m2.md` con:
```
status: DONE
correlation_id: <uuid-v4>   ← OBBLIGATORIO
branch: task/log_viewer_m2
```
