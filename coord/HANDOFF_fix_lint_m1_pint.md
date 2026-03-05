## HANDOFF_fix_lint_m1_pint.md

### Metadata
- task: TASK_fix_lint_m1_pint
- status: DONE
- correlation_id: 7e4a1b8c-3d9f-4c2e-a5b7-0f1e6d8c2a93
- run_id: executor-v2-fix-lint-m1-pint-20260228
- created: 2026-02-28T00:00:00Z
- branch: task/fix_lint_m1_pint

### Summary
Eseguito `vendor/bin/pint` auto-fix su 6 file segnalati dal Reviewer Wave M0.
Fix puramente stilistici (spacing, quotes, indentazione, chaining) — nessuna logica modificata.
Verifica finale: `pint --test` → `{"result":"pass"}` · `php artisan test` → 11/11 PASS.

### Files changed
- app/Services/TrafficLightService.php — modificato (class_attributes_separation, binary_operator_spaces)
- app/Services/ExplainerService.php — modificato (single_quote, concat_space, binary_operator_spaces, phpdoc_align)
- config/invoice.php — modificato (binary_operator_spaces)
- database/migrations/2026_02_23_000001_create_invoices_queue_table.php — modificato (class_definition, method_chaining_indentation, braces_position)
- database/seeders/InvoiceQueueSeeder.php — modificato (concat_space, binary_operator_spaces)
- tests/Feature/DashboardTest.php — modificato (concat_space, method_chaining_indentation, binary_operator_spaces)

### Commands run
```
vendor/bin/pint --test (pre-fix)
  → FAIL — {"result":"fail","files":[6 file con issue]}

vendor/bin/pint app/Services/TrafficLightService.php app/Services/ExplainerService.php \
  config/invoice.php database/migrations/2026_02_23_000001_create_invoices_queue_table.php \
  database/seeders/InvoiceQueueSeeder.php tests/Feature/DashboardTest.php
  → PASS — {"result":"fixed","files":[6 file corretti]}

vendor/bin/pint --test (post-fix)
  → PASS — {"result":"pass"}

php artisan test --stop-on-failure
  → PASS — 11/11 tests passed (30 assertions) — 9 WARN PHPUnit 12 deprecation (@test in doc-comment, pre-existing, non bloccante)
```

### Assunzioni fatte
- [A1] Pint ha modificato solo spaziatura/virgolette/indentazione — verificato: nessun comportamento cambiato (test 11/11 PASS).
- [A2] Il warning PHPUnit 12 sui `@test` doc-comment è pre-existing (era già presente in Wave M0) e non bloccante nell'MVP (PHPUnit 11 in uso).

### Rischi / TODO residui
- P2 (PHPUnit 12): I 9 metodi di test usano ancora `/** @test */` in doc-comment anziché l'attributo `#[Test]`. Non bloccante ora (PHPUnit 11), ma da migrare prima dell'upgrade a PHPUnit 12. Suggerito: tracciare in TASK_tests_m1_smoke o task dedicato M2.
- Nessun altro rischio residuo su questo task.

### Sblocca
- TASK_tests_m1_smoke — il file tests/Feature/DashboardTest.php è ora pint-clean
- TASK_guardrails_m1_core — gate pint globale verde
