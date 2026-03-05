# BOARD.md — invoice-ai-monitor
> Governance operativa · Complexity MED · Aggiornato: 2026-03-03 — Wave M0 WAVE_PASSED ✅ · Wave M1 WAVE_PASSED ✅ · Wave M2 WAVE_PASSED ✅

---

## 1. Tabella ruoli

| Ruolo           | Responsabilità                                   | Allowed paths                                              | Risk tier | Tooling          |
|-----------------|--------------------------------------------------|------------------------------------------------------------|-----------|------------------|
| Scaffold Agent  | Setup Laravel Herd, composer, env, DB locale     | Makefile, .env.example, .env, composer.json                | LOW       | Claude           |
| DB Agent        | Migration, seeder, schema view MySQL             | database/migrations/, database/seeders/, app/Models/       | MED       | Qwen Coder       |
| Feature Agent   | TrafficLightService, ExplainerService            | app/Services/, app/Http/Controllers/, routes/web.php       | MED       | Qwen Coder       |
| View Agent      | Blade templates, CSS inline, JS polling          | resources/views/                                           | LOW       | Claude           |
| Test Agent      | Feature test PHPUnit, smoke test                 | tests/                                                     | LOW       | Claude           |
| Guardrails Agent| Error handling, logging, AppException            | app/Exceptions/, app/Providers/                            | MED       | Claude           |
| Docs Agent      | README, SPEC.md update, runbook                  | README.md, SPEC.md, coord/                                 | LOW       | Claude           |

---

## 2. Routing agenti AI

```
HIGH (security / PII / auth / audit)   → Codex o umano
  → Nessun componente nell'MVP [A2]
  → Se si aggiunge autenticazione post-MVP: RICHIEDE revisione umana

MED  (feature / query DB / integrazioni)   → Qwen Coder
  → TrafficLightService, InvoiceQueue model, migration

LOW  (docs / views / test / refactor)      → Claude
  → Blade, README, test PHPUnit, ExplainerService (template puri)
```

---

## 3. Regole worktree

```
1. Ogni task parte da un branch dedicato:
     git checkout -b task/<TASKNAME>

2. Nessun agente modifica file fuori dal proprio "Allowed paths"
     → Se necessario: BLOCKED + crea TASK_fix_*

3. Merge su main solo con DoD PASS (vedi AGENTS.md §2)

4. Conflict detection:
     - Prima dell'assegnazione di un task, verificare overlap con task IN_PROGRESS
     - Se due task toccano lo stesso file: serializzare (non parallelizzare)
     - Vedi Parallelism Matrix in AGENTS.md §9
```

---

## 4. Protocollo HANDOFF

Schema fisso: vedere `AGENTS.md §8 — HANDOFF SCHEMA`.

Flusso:
```
Executor → produce HANDOFF_<TASK>.md (status: DONE | BLOCKED | NEEDS_REVIEW)
         → Reviewer legge HANDOFF → verifica DoD
         → Se PASS: merge + update BOARD
         → Se FAIL: rimanda a Executor (max 2 iterazioni, poi NEEDS_REVIEW)
```

---

## 5. Task board — [UPDATED: Reviewer Agent v2 · 2026-02-24 · Wave M0 APPROVED]

### Wave M0 — WAVE_PASSED ⚠️ (Reviewer v2 + Integration Guard v1 · 2026-02-24)

| Task ID                        | Milestone | Status              | Assignee | Blocked by       | Risk | Verdict Reviewer        |
|-------------------------------|-----------|---------------------|----------|------------------|------|-------------------------|
| TASK_scaffold_m0_boot          | M0        | DONE ✅ APPROVED ✅  | Claude   | —                | LOW  | APPROVED (P2 format)    |
| TASK_db_m0_schema              | M0        | DONE ✅ APPROVED ✅  | Claude   | scaffold ✅       | MED  | APPROVED                |
| TASK_semaforo_m0_core          | M0        | DONE ✅ APPROVED ✅  | Claude   | db_schema ✅      | MED  | APPROVED + fix tracked² |
| TASK_dashboard_m0_view         | M0        | DONE ✅ APPROVED ✅  | Claude   | semaforo ✅       | LOW  | APPROVED (P2 notes)     |

> ² `semaforo_core` ha un P1 (pint su TrafficLightService.php) delegato a TASK_fix_lint_m1_pint.

### Wave M1 — WAVE_PASSED ✅ (2026-02-28)

| Task ID                        | Milestone | Status              | Assignee | Blocked by                    | Risk | Completato   |
|-------------------------------|-----------|---------------------|----------|-------------------------------|------|--------------|
| TASK_fix_lint_m1_pint          | M1        | DONE ✅ APPROVED ✅  | Claude   | — (prerequisito M1 merge)     | LOW  | 2026-02-28   |
| TASK_guardrails_m1_core        | M1        | DONE ✅ APPROVED ✅  | Claude   | scaffold ✅                   | MED  | 2026-02-28   |
| TASK_tests_m1_smoke            | M1        | DONE ✅ APPROVED ✅  | Claude   | scaffold ✅ ¹                 | LOW  | 2026-02-28   |
| TASK_docs_m1_runbook           | M1        | DONE ✅ APPROVED ✅  | Claude   | guardrails ✅ + tests ✅       | LOW  | 2026-02-28   |
| ~~TASK_spiegami_m0_explain~~   | —         | OVERFLOW            | —        | → Complexity Manager          | LOW  | —            |
| ~~TASK_scheduler_m0_cron~~     | —         | OVERFLOW            | —        | → Complexity Manager          | MED  | —            |

> ¹ `tests_m1_smoke` DoD aggiornato: deve includere test TrafficLight (3 scenari) + Explainer (3 stati).
> ⚠️ **OVERFLOW**: 2 task rimandati al Complexity Manager (totale: 9 > 7 threshold MED).

### Ordine consigliato Wave M1
```
1. TASK_fix_lint_m1_pint       ← primo (sblocca pint gate)
2. TASK_guardrails_m1_core     ← parallelizzabile con tests
   TASK_tests_m1_smoke         ← parallelizzabile con guardrails
3. TASK_docs_m1_runbook        ← dopo guardrails + tests DONE
```

### Wave M2 — WAVE_PASSED ✅ (2026-03-03 · Reviewer v2 · tutti APPROVED)

| Task ID                        | Milestone | Status              | Assignee | Blocked by                      | Risk |
|-------------------------------|-----------|---------------------|----------|---------------------------------|------|
| TASK_01_log_viewer_m2          | M2        | DONE ✅ APPROVED ✅  | Claude   | Wave M1 DONE ✅                  | LOW  |
| TASK_02_chat_ai_m2             | M2        | DONE ✅ APPROVED ✅  | Claude   | TASK_01_log_viewer_m2 ✅         | MED  |
| TASK_03_spiegami_dynamic_m2    | M2        | DONE ✅ APPROVED ✅  | Claude   | TASK_02_chat_ai_m2 ✅            | MED  |
| TASK_04_scheduler_m2           | M2        | DONE ✅ APPROVED ✅  | Claude   | TASK_03_spiegami_dynamic_m2 ✅   | MED  |

### Ordine Wave M2 (sequenziale)
```
1. TASK_01_log_viewer_m2          ← nessuna dipendenza M2 (sblocca TASK_02)
2. TASK_02_chat_ai_m2             ← dopo TASK_01 DONE (introduce infrastruttura AI)
3. TASK_03_spiegami_dynamic_m2    ← dopo TASK_02 DONE (riusa config AI e SDK)
4. TASK_04_scheduler_m2           ← dopo TASK_03 DONE (usa ExplainerService aggiornato)
```

> ✅ TASK_02 completato con Gemini 1.5 Flash (GEMINI_API_KEY) — override Anthropic su indicazione utente.
> ✅ TASK_03 ora sbloccato — riusa `config/ai.php` e pattern ChatService.

### Backlog M2 — COMPLETATO ✅ (2026-03-03)

| Task ID                          | Milestone | Status              | Assignee | Blocked by                              | Risk |
|---------------------------------|-----------|---------------------|----------|-----------------------------------------|------|
| TASK_fix_phpunit_attributes_m2   | M2 BL     | DONE ✅ APPROVED ✅  | Claude   | —                                       | LOW  |
| TASK_index_graceful_m2           | M2 BL     | DONE ✅ APPROVED ✅  | Claude   | TASK_fix_phpunit_attributes_m2 ✅        | LOW  |

> ✅ **Backlog M2: WAVE_PASSED** (Integration Guard v1 · 2026-03-03)
> INTEGRATION_REPORT_backlog_m2.md: 34/34 PASS · 82 assertions · pint PASS · 0 @test · 8/8 exit conditions

### Hotfix post-M2 (2026-03-03)

| Task ID                  | Milestone | Status              | Assignee | Note                                      |
|--------------------------|-----------|---------------------|----------|-------------------------------------------|
| chat_retry_fix           | hotfix    | DONE ✅              | Claude   | AI_MODEL→gemini-2.0-flash + retry 3× 429 |

### Wave M3 — IN CORSO (2026-03-03)

| Task ID               | Milestone | Status | Assignee | Blocked by          | Risk |
|-----------------------|-----------|--------|----------|---------------------|------|
| TASK_simulator_m3     | M3        | DONE ✅ APPROVED ✅ | Claude   | chat_retry_fix ✅    | MED  |

## 6. Parallelism matrix — [UPDATED: completato da Planner Agent v3 · 2026-02-23]

| Task A                  | Task B                   | Parallel? | Motivo                                         |
|-------------------------|--------------------------|-----------|------------------------------------------------|
| scaffold_m0_boot        | db_m0_schema             | ❌ NO     | db_schema richiede artisan + DB pronto         |
| scaffold_m0_boot        | guardrails_m1_core       | ❌ NO     | guardrails richiede app scaffoldata            |
| scaffold_m0_boot        | tests_m1_smoke           | ❌ NO     | tests richiede artisan + RefreshDatabase       |
| db_m0_schema            | guardrails_m1_core       | ✅ SÌ     | path disgiunti (database/ vs app/Exceptions/)  |
| db_m0_schema            | tests_m1_smoke           | ✅ SÌ     | path disgiunti (database/ vs tests/)           |
| guardrails_m1_core      | tests_m1_smoke           | ✅ SÌ     | path disgiunti (app/Exceptions/ vs tests/)     |
| semaforo_m0_core        | guardrails_m1_core       | ✅ SÌ     | path disgiunti (app/Services/ vs app/Exceptions/) |
| semaforo_m0_core        | tests_m1_smoke           | ✅ SÌ     | path disgiunti — test falliscono finché service non è pronto (TDD OK) |
| semaforo_m0_core        | dashboard_m0_view        | ❌ NO     | dashboard dipende dai Service implementati     |
| tests_m1_smoke          | docs_m1_runbook          | ❌ NO     | docs aggiorna SPEC.md M0 solo se test PASS     |
| guardrails_m1_core      | docs_m1_runbook          | ❌ NO     | docs documenta comportamento 503 di guardrails |
| TASK_01_log_viewer_m2   | TASK_02_chat_ai_m2       | ❌ NO     | TASK_02 usa route/controller introdotti da TASK_01 |
| TASK_02_chat_ai_m2      | TASK_03_spiegami_dynamic_m2 | ❌ NO  | TASK_03 riusa config/ai.php e SDK Anthropic da TASK_02 |
| TASK_03_spiegami_dynamic_m2 | TASK_04_scheduler_m2 | ❌ NO    | TASK_04 usa ExplainerService aggiornato da TASK_03 |
