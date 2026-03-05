# TASK_semaforo_m0_core

## Metadata
- **created**: 2026-02-23T00:00:00Z
- **updated**: 2026-02-24T15:55:00Z
- **assignee**: Claude
- **status**: DONE ✅
- **milestone**: M0
- **risk tier**: MED

---

## Obiettivo
Implementare e validare `TrafficLightService` (snapshot + eventi) e `ExplainerService` (testo italiano plain-language). Le soglie devono essere lette da `config/invoice.php` e la cache deve ridurre le query al DB. Al termine: tutti i test `--filter=TrafficLight` e `--filter=Explainer` → PASS.

---

## Scope

### TrafficLightService
- [ ] `snapshot()` → `array{status: string, pending: int, errors: int, last_updated: string}`
- [ ] `computeStatus(int $pending, int $errors): string` → `'green'|'yellow'|'red'`
- [ ] Soglie: `config('invoice.semaforo_yellow_pending')`, `config('invoice.semaforo_yellow_errors')`, `config('invoice.semaforo_red_pending')`, `config('invoice.semaforo_red_errors')`
- [ ] `recentEvents(int $limit = 20)` → `Collection<InvoiceQueue>` ordinata per `updated_at DESC`
- [ ] Cache `Cache::remember('semaforo_snapshot', config('invoice.cache_ttl_seconds', 30), fn())` [R3]

### ExplainerService
- [ ] `explain(array $snapshot): string` → match su `$snapshot['status']`
- [ ] Testo **verde**: menziona pending e errori, conclude "Nessuna azione richiesta"
- [ ] Testo **giallo**: indica le soglie superate, suggerisce monitoraggio entro 30 min
- [ ] Testo **rosso**: parole chiave "PROBLEMA CRITICO", "Contatta immediatamente il supporto tecnico"
- [ ] Nessun termine tecnico non spiegato nel testo (nessun "HTTP", "SQL", "exception", "stack trace")
- [ ] Soglie leggibili da `config('invoice.semaforo_*')` anche nei testi esplicativi

### Entrambi
- [ ] Dichiarazione `declare(strict_types=1)` in testa
- [ ] Nessun `catch {}` vuoto — ogni eccezione viene loggata [preparatoria per guardrails]

## Non-scope
- NON integrare LLM esterni (template PHP puri) [A3]
- NON aggiungere autenticazione o middleware
- NON modificare Controller o route (verranno gestiti in TASK_dashboard)
- NON modificare il DB schema

---

## Allowed paths
```
app/Services/TrafficLightService.php
app/Services/ExplainerService.php
app/Models/InvoiceQueue.php         ← solo scope methods se mancanti
config/invoice.php                  ← solo lettura/verifica chiavi semaforo_*
```

## Forbidden paths
```
app/Http/Controllers/
resources/views/
routes/
database/migrations/
database/seeders/
tests/                              ← no scrittura test qui (task separato)
Dockerfile
docker-compose.yml
```

---

## Dipendenze
- **BLOCKED_BY**: TASK_db_m0_schema (tabella deve essere seedata per test integrazione)
- **BLOCKS**: TASK_dashboard_m0_view
- **Pre-check**: TASK_db_m0_schema → status DONE? **NO** → stato BLOCKED

---

## Definition of Done

```bash
# 1. Test specifici servizi
docker compose exec app php artisan test --filter=TrafficLight
# → PASS (minimo: verde/giallo/rosso con i 3 scenari seeder)

docker compose exec app php artisan test --filter=Explainer
# → PASS (minimo: testi italiano per i 3 stati)

# 2. Verifica cache (manuale)
docker compose exec app php artisan tinker \
  --execute="app(\App\Services\TrafficLightService::class)->snapshot();"
# → PASS se ritorna array con chiavi status, pending, errors, last_updated

# 3. Linter
docker compose exec app vendor/bin/pint --test
# → PASS

# 4. Nessuna regressione
docker compose exec app php artisan test --stop-on-failure
# → PASS

# 5. HANDOFF prodotto
ls coord/HANDOFF_semaforo_m0_core.md
# → PASS se esiste con correlation_id
```

---

## Comandi verifica rapida
```bash
# Verifica snapshot diretto
docker compose exec app php artisan tinker \
  --execute="dump(app(\App\Services\TrafficLightService::class)->snapshot());"

# Verifica testo spiegami
docker compose exec app php artisan tinker \
  --execute="dump(app(\App\Services\ExplainerService::class)->explain(['status'=>'red','pending'=>55,'errors'=>11]));"
# → deve contenere "PROBLEMA CRITICO"
```

---

## Contratto I/O atteso

### TrafficLightService::snapshot()
```json
{
  "status": "red",
  "pending": 55,
  "errors": 11,
  "last_updated": "2026-02-23T09:00:00+01:00"
}
```

### ExplainerService::explain() — stato rosso
```
"PROBLEMA CRITICO: 55 fatture bloccate in attesa di invio e 11 fatture in errore o rifiutate.
Il sistema di fatturazione potrebbe essere bloccato o in errore grave.
Contatta immediatamente il supporto tecnico. Non attendere."
```

---

## Template HANDOFF da produrre

Creare `coord/HANDOFF_semaforo_m0_core.md` con:
```
status: DONE
correlation_id: <uuid-v4>   ← OBBLIGATORIO
branch: task/semaforo_m0_core
```

---

## Assunzioni
- [A3] `ExplainerService` usa template PHP statici — nessun LLM nell'MVP. Se si aggiunge un LLM in futuro: creare TASK_llm_explainer e rivedere sezione integrazioni in SPEC.md
- [A6] Intervallo cache TTL = `CACHE_TTL_SECONDS` (default 30s) — configurabile senza deploy
