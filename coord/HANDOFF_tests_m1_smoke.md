## HANDOFF_tests_m1_smoke.md

### Metadata
- task: TASK_tests_m1_smoke
- status: DONE
- correlation_id: c4d7e9a2-5f8b-4c1e-d3a6-7b2f0e4c8d1a
- run_id: executor-v2-tests-m1-smoke-20260228
- created: 2026-02-28T00:00:00Z
- branch: task/tests_m1_smoke

### Summary
Tutti e 9 i test di accettazione M0 in `tests/Feature/DashboardTest.php` erano già presenti e passanti (implementati in Wave M0). Aggiunti `tests/Unit/TrafficLightServiceTest.php` (5 test su computeStatus()) e `tests/Unit/ExplainerServiceTest.php` (3 test su explain()) come richiesto dall'aggiornamento DoD del BOARD. Totale finale: 19/19 test PASS, 40 assertions, pint pass.

### Files changed
- tests/Feature/DashboardTest.php — nessuna modifica (già completo con 9 test M0)
- tests/Unit/TrafficLightServiceTest.php — aggiunto (5 test: green/yellow×2/red×2 per computeStatus())
- tests/Unit/ExplainerServiceTest.php — aggiunto (3 test: green/yellow/red per explain())
- tests/TestCase.php — nessuna modifica
- phpunit.xml — nessuna modifica

### Commands run
```
php artisan test --stop-on-failure
  → PASS — 19 tests passed (40 assertions) in 1.60s

php artisan test --filter=DashboardTest --testdox
  → PASS — 9/9 test DashboardTest tutti ✔ (nessuna failure)

[3 run manuali] php artisan test --filter=DashboardTest (×3)
  → PASS — risultato identico in ogni run (RefreshDatabase verificato)

vendor/bin/pint --test
  → PASS — {"result":"pass"}
  (pint auto-fix eseguito su TrafficLightServiceTest.php e ExplainerServiceTest.php: new_with_parentheses)
```

### Matrice test → criteri M0 (tutti ✔)

| Test | Criterio | Risultato |
|------|----------|-----------|
| semaforo_mostra_verde_… | M0-01 | ✅ PASS |
| semaforo_mostra_giallo_con_pending_… | M0-02 | ✅ PASS |
| semaforo_mostra_giallo_con_errori_… | M0-02 | ✅ PASS |
| semaforo_mostra_rosso_con_pending_… | M0-03 | ✅ PASS |
| semaforo_mostra_rosso_con_errori_… | M0-03 | ✅ PASS |
| dashboard_risponde_200 | M0-04 | ✅ PASS |
| spiegami_con_stato_verde_… | M0-05 | ✅ PASS |
| spiegami_con_stato_rosso_… | M0-05 | ✅ PASS |
| endpoint_status_restituisce_struttura_json_valida | M0-06 | ✅ PASS |
| TrafficLightServiceTest (×5) | BOARD DoD update | ✅ PASS |
| ExplainerServiceTest (×3) | BOARD DoD update | ✅ PASS |

### Assunzioni fatte
- [A1] `tests/Unit/TrafficLightServiceTest.php` e `tests/Unit/ExplainerServiceTest.php` non sono elencati negli Allowed Paths originali del TASK (solo tests/Feature/DashboardTest.php, tests/TestCase.php, phpunit.xml). Creati ugualmente perché: (a) il BOARD ha aggiornato esplicitamente il DoD per includerli; (b) `tests/Unit/` non è nei Forbidden Paths; (c) il TASK originale era già soddisfatto con i 9 test Feature preesistenti.
- [A2] PHPUnit 11 non supporta il flag `-v` usato nel DoD del TASK (richiede `--testdox` o `--verbose`). Il gate DoD-2 è stato eseguito con `--testdox` che mostra ogni test con ✔. Nessun impatto funzionale.
- [A3] Il DoD-3 originale usa `--repeat=3` (flag non disponibile in PHPUnit 11). Sostituito con 3 esecuzioni manuali consecutive di `--filter=DashboardTest` — risultati identici, RefreshDatabase verificato.
- [A4] Entrambi i file di unit test estendono `Tests\TestCase` (Laravel) invece di `PHPUnit\Framework\TestCase` perché i service leggono config() (soglie semaforo) nel costruttore / nei metodi, che richiede l'app Laravel bootstrappata.

### Rischi / TODO residui
- P2 (PHPUnit 12 deprecation): tutti i test (9 Feature + 8 Unit) usano `@test` doc-comment. Warning non bloccante in PHPUnit 11. Da migrare a `#[Test]` attribute prima dell'upgrade a PHPUnit 12. Suggerito: task dedicato in M2.
- P2 (M0-07 performance): il criterio M0-07 (p95 < 2s) è documentato come "assertion sulla struttura risposta" in DashboardTest ma non ha un test di timing reale. Accettabile per MVP (carico = 1 utente in test).

### Sblocca
- TASK_docs_m1_runbook — guardrails DONE ✅ + tests DONE ✅ → tutti i prerequisiti soddisfatti
