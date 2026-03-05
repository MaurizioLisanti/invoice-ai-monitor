# TASK_tests_m1_smoke

## Metadata
- **created**: 2026-02-23T00:00:00Z
- **updated**: 2026-02-23T00:00:00Z  ← completato da Planner Agent v3
- **assignee**: Claude
- **status**: BLOCKED
- **milestone**: M1
- **risk tier**: LOW

---

## Obiettivo
Completare e validare la suite PHPUnit che copre tutti e 7 i criteri di accettazione M0. Ogni test deve essere PASS/FAIL binario, riproducibile in < 5 minuti con `make test`. Nessun test flaky (no sleep, no dipendenze da orario).

> ⚠️ Questo task può essere eseguito **in parallelo** con `TASK_guardrails_m1_core` — i path sono disgiunti.

---

## Scope

### Test da implementare/verificare in `tests/Feature/DashboardTest.php`
- [ ] **M0-01a** `semaforo_mostra_verde_con_zero_errori_e_pending_nei_limiti` → `status: green`
- [ ] **M0-02a** `semaforo_mostra_giallo_con_pending_oltre_soglia` (15 pending) → `status: yellow`
- [ ] **M0-02b** `semaforo_mostra_giallo_con_errori_oltre_soglia` (5 errori) → `status: yellow`
- [ ] **M0-03a** `semaforo_mostra_rosso_con_pending_oltre_soglia` (55 pending) → `status: red`
- [ ] **M0-03b** `semaforo_mostra_rosso_con_errori_oltre_soglia` (12 errori) → `status: red`
- [ ] **M0-04**  `dashboard_risponde_200` → HTTP 200 + page contains "invoice-ai-monitor"
- [ ] **M0-06**  `endpoint_status_restituisce_struttura_json_valida` → JSON con chiavi `status`, `pending`, `errors`, `last_updated`
- [ ] **M0-05a** `spiegami_con_stato_verde_restituisce_testo_rassicurante` → testo contiene "correttamente"
- [ ] **M0-05b** `spiegami_con_stato_rosso_restituisce_testo_critico` → testo contiene "PROBLEMA" e "supporto tecnico"
- [ ] Tutti i test: `use RefreshDatabase` (DB pulito ad ogni test)
- [ ] Helper `seedRows(string $status, int $count)` riutilizzabile in tutti i test
- [ ] Nessun `sleep()` o dipendenza da timestamp nel codice test
- [ ] `phpunit.xml`: configurato con `<env name="APP_ENV" value="testing"/>` e DB in-memory o DB test dedicato

## Non-scope
- NON testare comportamento CSS o JS (solo asserzioni HTTP + JSON)
- NON test di carico/stress/performance (non M0)
- NON mock di servizi (TrafficLightService e ExplainerService sono reali sui test)
- NON test browser/Selenium/Dusk

---

## Allowed paths
```
tests/Feature/DashboardTest.php
tests/TestCase.php                   ← solo se override base setup necessario
phpunit.xml
```

## Forbidden paths
```
app/Services/
app/Http/
app/Models/
database/migrations/
resources/
routes/
config/
Dockerfile
docker-compose.yml
coord/
```

---

## Dipendenze
- **BLOCKED_BY**: TASK_scaffold_m0_boot (container e DB devono essere operativi)
- **BLOCKS**: TASK_docs_m1_runbook
- **PARALLEL con**: TASK_guardrails_m1_core (path disgiunti — sicuro)
- **Pre-check**: TASK_scaffold_m0_boot → status DONE? **NO** → stato BLOCKED

> Nota: i test dipendono logicamente anche da `TASK_semaforo_m0_core` e `TASK_dashboard_m0_view`
> per passare tutti. Tuttavia il task tests_smoke può essere sviluppato in parallelo:
> i test falliscono finché le implementazioni non sono complete — è il comportamento atteso (TDD).

---

## Definition of Done

```bash
# 1. Tutti i test passano
docker compose exec app php artisan test --stop-on-failure
# → PASS: X tests, 0 failures, 0 errors (X ≥ 9)

# 2. Test nominali
docker compose exec app php artisan test --filter=DashboardTest -v
# → PASS se ogni test viene elencato con ✓

# 3. RefreshDatabase verificato (nessun side effect tra test)
docker compose exec app php artisan test --filter=DashboardTest --repeat=3
# → PASS se i 3 run producono lo stesso risultato

# 4. Linter
docker compose exec app vendor/bin/pint --test
# → PASS

# 5. HANDOFF prodotto
ls coord/HANDOFF_tests_m1_smoke.md
# → PASS se esiste con correlation_id
```

---

## Matrice test ↔ criteri M0

| Test                                              | Criterio M0 | Soglia verificata        |
|---------------------------------------------------|-------------|--------------------------|
| `semaforo_mostra_verde_…`                         | M0-01       | pending≤10, errors≤3     |
| `semaforo_mostra_giallo_con_pending_…`            | M0-02       | pending>10               |
| `semaforo_mostra_giallo_con_errori_…`             | M0-02       | errors>3                 |
| `semaforo_mostra_rosso_con_pending_…`             | M0-03       | pending>50               |
| `semaforo_mostra_rosso_con_errori_…`              | M0-03       | errors>10                |
| `dashboard_risponde_200`                          | M0-04       | HTTP 200                 |
| `endpoint_status_restituisce_struttura_json_…`    | M0-06       | JSON struttura           |
| `spiegami_con_stato_verde_…`                      | M0-05       | testo "correttamente"    |
| `spiegami_con_stato_rosso_…`                      | M0-05       | "PROBLEMA"+"supporto…"   |

---

## Template HANDOFF da produrre

Creare `coord/HANDOFF_tests_m1_smoke.md` con:
```
status: DONE
correlation_id: <uuid-v4>   ← OBBLIGATORIO
branch: task/tests_m1_smoke
```
