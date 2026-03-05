# TASK_fix_lint_m1_pint

## Metadata
- **created**: 2026-02-24T16:30:00Z
- **updated**: 2026-02-24T16:30:00Z
- **assignee**: Claude
- **status**: TODO — PRONTO
- **milestone**: M1 (prerequisito ante merge M1)
- **risk tier**: LOW
- **creato da**: Reviewer Agent v2 (PROMPT_05) — Wave M0 review

---

## Obiettivo
Correggere tutti i fallimenti `vendor/bin/pint --test` rimasti dopo Wave M0.
Uno è stato introdotto dall'executor (TrafficLightService.php — P1 NEEDS_CHANGES
su semaforo_core); gli altri sono pre-existing dal repo seed.
Al termine: `vendor/bin/pint --test` deve restituire `{"result":"pass"}` globalmente.

---

## Contesto — perché questo task esiste
Il Reviewer (PROMPT_05) ha rilevato che `vendor/bin/pint --test` fallisce su 6 file
dopo Wave M0. Il task è stato separato in un fix dedicato perché:
- I fix attraversano più Allowed Paths di task diversi
- La natura dei fix è puramente stilistica (nessun comportamento cambia)
- Tutti i test funzionali passano già (11/11 PASS)

---

## Scope — file da correggere

```
app/Services/TrafficLightService.php    ← P1 (introdotto da semaforo_core)
app/Services/ExplainerService.php       ← pre-existing
config/invoice.php                      ← pre-existing
database/migrations/2026_02_23_000001_create_invoices_queue_table.php  ← pre-existing
database/seeders/InvoiceQueueSeeder.php ← pre-existing
tests/Feature/DashboardTest.php         ← pre-existing
```

**Comandi da eseguire (in ordine):**
```bash
vendor/bin/pint app/Services/TrafficLightService.php
vendor/bin/pint app/Services/ExplainerService.php
vendor/bin/pint config/invoice.php
vendor/bin/pint database/migrations/2026_02_23_000001_create_invoices_queue_table.php
vendor/bin/pint database/seeders/InvoiceQueueSeeder.php
vendor/bin/pint tests/Feature/DashboardTest.php
```

---

## Non-scope
- NON modificare la logica dei file (solo stile — pint auto-fix)
- NON aggiungere funzionalità
- NON modificare file non elencati sopra

---

## Allowed paths
```
app/Services/TrafficLightService.php
app/Services/ExplainerService.php
config/invoice.php
database/migrations/2026_02_23_000001_create_invoices_queue_table.php
database/seeders/InvoiceQueueSeeder.php
tests/Feature/DashboardTest.php
```

## Forbidden paths
```
app/Http/Controllers/
resources/views/
routes/
app/Models/
```

---

## Dipendenze
- **BLOCKED_BY**: nessuno — PRONTO
- **BLOCKS**: TASK_tests_m1_smoke (il test file sarà già pint-clean), TASK_guardrails_m1_core

---

## Definition of Done

```bash
# 1. Pint globale
vendor/bin/pint --test
# → PASS — {"result":"pass"}  (nessun file con issue)

# 2. Test suite — nessuna regressione
php artisan test --stop-on-failure
# → PASS — 11/11 tests passed

# 3. HANDOFF prodotto
ls coord/HANDOFF_fix_lint_m1_pint.md
# → PASS se esiste con correlation_id
```

---

## Assunzioni
- [A1] `pint` auto-fix modifica solo spaziatura/virgolette/indentazione — nessun
  comportamento cambia (verificare diff prima di commit)
- [A2] Se pint introduce conflitti nel test file (es. indentazione nelle assertion
  multi-riga), il test deve continuare a passare — verificare con `php artisan test`
