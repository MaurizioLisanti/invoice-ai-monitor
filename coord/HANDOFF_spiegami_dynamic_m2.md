## HANDOFF_spiegami_dynamic_m2.md

### Metadata
- task: TASK_03_spiegami_dynamic_m2
- status: DONE
- correlation_id: b7e2f4a1-9c3d-4e6b-8f0a-2d5c7e9b1f3a
- run_id: executor-v2-spiegami-dynamic-m2-20260302
- created: 2026-03-02T00:00:00Z
- branch: task/spiegami_dynamic_m2

### Summary
Refactoring di `ExplainerService` con percorso LLM opzionale (Gemini 1.5 Flash, stesso config/ai.php di ChatService) e fallback graceful ai template statici M0. Flag `EXPLAINER_USE_LLM` in `.env.example` e `config/invoice.php`. Firma pubblica `explain(array $snapshot): string` invariante — nessun cambio a controller o view. 3 nuovi test Unit con `Http::fake()` coprono: risposta LLM valida, HTTP error 403 → fallback, risposta malformata/vuota → fallback.

### Files changed
- `app/Services/ExplainerService.php` — modificato (aggiunto percorso LLM: `explainWithLlm()`, `buildSystemPrompt()`; logica template M0 spostata in `explainWithTemplate()` e metodi privati invarianti)
- `config/invoice.php` — modificato (aggiunta chiave `explainer_use_llm`)
- `tests/Unit/ExplainerServiceTest.php` — modificato (aggiunti 3 test LLM con Http::fake; 3 test template M0 invarianti)
- `.env.example` — modificato (aggiunta sezione Spiegami dinamico con `EXPLAINER_USE_LLM=true`)

### Commands run
```
php artisan test --stop-on-failure (pre-pint)
  → PASS — 30/30 tests passed (71 assertions) in 24.54s
  (6 ExplainerServiceTest: 3 template + 3 LLM; 24 pre-esistenti tutti PASS)

vendor/bin/pint --test
  → PASS — {"result":"pass"}
```

### Assunzioni fatte
- [A1] `config/ai.php` e `GEMINI_API_KEY` già presenti (da TASK_02) — nessuna nuova variabile di ambiente AI aggiunta.
- [A2] `explainWithLlm()` ritorna `null` su qualsiasi errore (timeout, HTTP non-2xx, risposta malformata/vuota) → graceful degradation al template M0 senza propagare eccezioni al controller. Coerente con il non-scope "nessun caching della risposta LLM".
- [A3] Il prompt di sistema include esplicitamente la lista di termini tecnici vietati (HTTP, SQL, exception, stack trace, PDO, query, database, cache, Laravel, API) come da invariante SPEC M0-05. Sufficiente per MVP — in produzione serve test con LLM reale.
- [A4] I test LLM usano `Http::fake()` (nessuna rete reale). Il DoD-1/DoD-2/DoD-3 (curl live) non sono eseguibili in assenza di server attivo e GEMINI_API_KEY in .env — documentati come verifica manuale.
- [A5] `coord/` è nei Forbidden Paths del TASK_03 — HANDOFF prodotto ugualmente come da protocollo AGENTS.md §8 (stesso pattern P2-A di TASK_01/TASK_02).

### Rischi / TODO residui
- P2 (invariante M0-05 con LLM reale): la lista di termini vietati nel prompt è una best-effort — il LLM potrebbe occasionalmente violarla. Verificare manualmente al primo deploy con `GEMINI_API_KEY` reale e il grep del TASK.
- P2 (@test deprecation): i 3 nuovi test usano `@test` doc-comment (PHPUnit 12 warning — pre-existing, già tracciato nel backlog M2).
- P1 (pre-TASK_04): TASK_04_scheduler_m2 usa `ExplainerService` — verificare che il flag `EXPLAINER_USE_LLM` in `.env` sia allineato prima di avviare lo scheduler in produzione.

### Sblocca
- TASK_04_scheduler_m2 — dipendenza soddisfatta ✅
