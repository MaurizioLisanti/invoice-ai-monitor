# AGENTS.md — invoice-ai-monitor
> Governance multi-agente · PHP/Laravel · Complexity: MED

---

## 0. Dichiarazione stack
- **PKG**: `invoice_ai_monitor`
- **Stack**: PHP 8.2 · Laravel 11 · MySQL 8 · Docker
- **Test runner**: `php artisan test`
- **Linter**: `vendor/bin/pint`
- **Namespace**: `App\`
- **correlation_id**: NO (sistema single-agent MVP; aggiungere se si passa a multi-agente)

---

## 1. Workflow Git
```
1 task = 1 branch = 1 worktree isolato

Naming branch: task/<TASKNAME>
Esempi:
  task/scaffold_m0_boot
  task/db_m0_schema
  task/semaforo_m0_core

Merge policy:
  - Nessun merge diretto su main senza DoD PASS
  - Ogni merge produce HANDOFF_<TASKNAME>.md nella root
  - PR richiede diff summary e comandi DoD documentati
```

---

## 2. Definition of Done (DoD) — universale

Prima di dichiarare un task DONE, tutti i check devono essere PASS:

```bash
# 1. Test suite
docker compose exec app php artisan test --stop-on-failure
# → DEVE passare: 0 errori, 0 failure

# 2. Linter
docker compose exec app vendor/bin/pint --test
# → DEVE passare: "No files need style changes"

# 3. File HANDOFF prodotto
ls HANDOFF_<TASKNAME>.md
# → DEVE esistere con correlation_id compilato

# 4. Diff summary scritto nel HANDOFF
# → Sezione "Files changed" compilata con tutti i file toccati
```

---

## 3. Stop condition

Se un agente deve modificare file **fuori dai propri allowed paths**:
1. NON modificare il file
2. Creare `HANDOFF_<TASKNAME>.md` con `status: BLOCKED`
3. Compilare la sezione "Se BLOCKED" (file necessari, motivo, azione suggerita)
4. STOP — notificare il Planner

**Allowed paths per task** → vedi `coord/BOARD.md` → colonna "Allowed paths"

---

## 4. Logging

```php
// Formato obbligatorio: JSON strutturato
// Canale: stack (Laravel default)
// Livello minimo produzione: warning

// Esempio corretto:
Log::warning('semaforo_state_change', [
    'from'     => $old,
    'to'       => $new,
    'pending'  => $snapshot['pending'],
    'errors'   => $snapshot['errors'],
    'ts'       => now()->toIso8601String(),
]);

// VIETATO:
// Log::info("stato cambiato da $old a $new");  ← stringa non strutturata
```

Retention: 30 giorni (`storage/logs/`) [A4]
Audit trail: NO nell'MVP

---

## 5. Error model

```php
// Gerarchia base — NON fare fail silently

// AppException: eccezione base del dominio
// Tutti i servizi devono lanciare AppException (o sottoclassi)
// mai swallow eccezioni con catch vuoti

// Esempio:
try {
    $snapshot = $this->trafficLight->snapshot();
} catch (\Throwable $e) {
    Log::error('snapshot_failed', ['error' => $e->getMessage()]);
    throw new \App\Exceptions\AppException('Impossibile leggere lo stato semaforo', 0, $e);
}
```

---

## 6. Emergency stop

Se durante l'esecuzione di un task emerge uno dei seguenti:
- Dati PII / anagrafici in chiaro nei log
- Secret / credenziali nel codice o nei file tracciati da git
- Costo API stimato superiore ai budget dichiarati in SPEC.md

→ Creare `HALT.md` nella root con:
  - Descrizione del problema
  - File coinvolti
  - Azione raccomandata

→ STOP — non procedere fino a risoluzione umana

---

## 7. Convenzione moduli (PHP/Laravel)

```
app/
├── Http/Controllers/     → Controller HTTP (thin — delegano a Services)
├── Services/             → Business logic (TrafficLightService, ExplainerService)
├── Models/               → Eloquent models (read-only su DB fatturazione)
└── Exceptions/           → AppException e sottoclassi

database/
├── migrations/           → Solo migrations di DEV/TEST [A1]
└── seeders/              → Scenari di test (verde/giallo/rosso)

resources/views/          → Blade templates

routes/web.php            → Route definitions (solo GET nell'MVP)

tests/Feature/            → Test di accettazione M0
```

---

## 8. HANDOFF SCHEMA — fisso

Questo schema è condiviso tra Executor (prompt 04) e Reviewer (prompt 05).
Usare verbatim per garantire parsing coerente.

```markdown
## HANDOFF_<TASKNAME>.md — Schema fisso

### Metadata
- task: TASK_<nome>
- status: DONE | BLOCKED | NEEDS_REVIEW
- correlation_id: <uuid-v4>          ← OBBLIGATORIO
- run_id: <identificatore-run>       ← OBBLIGATORIO
- created: <ISO8601>
- branch: task/<TASKNAME>

### Summary
[max 3 righe — cosa è stato fatto]

### Files changed
- path/al/file.ext — [aggiunto | modificato | eliminato]

### Commands run
\`\`\`
[comando] → [output sintetico: PASS / FAIL / N righe]
\`\`\`

### Assunzioni fatte
- [A1] ...

### Rischi / TODO residui
- ...

### Se BLOCKED
- File che servirebbero fuori scope: [lista path]
- Motivo: ...
- Azione suggerita: [crea TASK_fix_* o aggiorna dipendenze]

### Se NEEDS_REVIEW
- Motivo del fallimento: ...
- Iterazioni tentate: [1 / 2]
- Tipo errore: [implementazione / ambiente / dipendenza]
```

---

## 9. Parallelism Matrix

| Task A                  | Task B                   | Parallel? | Motivo                                  |
|-------------------------|--------------------------|-----------|-----------------------------------------|
| scaffold_m0_boot        | db_m0_schema             | NO        | db_schema dipende dal container avviato |
| db_m0_schema            | guardrails_m1_core       | SÌ        | path disgiunti                          |
| semaforo_m0_core        | dashboard_m0_view        | NO        | view dipende dal service                |
| guardrails_m1_core      | tests_m1_smoke           | SÌ        | path disgiunti                          |
| tests_m1_smoke          | docs_m1_runbook          | SÌ        | docs non blocca i test                  |
