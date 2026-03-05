## HANDOFF_scheduler_m2.md

### Metadata
- task: TASK_04_scheduler_m2
- status: DONE
- correlation_id: d3a7f1c5-6b2e-4d8a-9c0f-1e5b3a7d2c9e
- run_id: executor-v2-scheduler-m2-20260302
- created: 2026-03-02T00:00:00Z
- branch: task/scheduler_m2

### Summary
Implementato `CheckInvoiceQueueCommand` (invoice:check-queue): legge snapshot via TrafficLightService, emette Log::info 'queue_check' ad ogni ciclo e Log::critical 'queue_alert_red' se status='red'; AppException → Log::error + exit FAILURE. Schedule registrato in `routes/console.php` ogni minuto con `ConsoleApplication::starting()` per auto-registrazione classe in Laravel 11. Target `make cron` aggiunto al Makefile. Sezione Scheduler aggiunta al README con istruzioni avvio locale e cron di produzione. 3 test Unit con Log::spy + TrafficLightService mock.

### Files changed
- `app/Console/Commands/CheckInvoiceQueueCommand.php` — aggiunto (nuovo comando Artisan)
- `routes/console.php` — modificato (ConsoleApplication::starting + Schedule::command)
- `Makefile` — modificato (target `cron` + .PHONY)
- `README.md` — modificato (sezione "Scheduler" con istruzioni locale e produzione)
- `tests/Unit/CheckInvoiceQueueCommandTest.php` — aggiunto (3 test: success/info, red/critical, exception/failure)

### Commands run
```
php artisan test --stop-on-failure
  → PASS — 33/33 tests passed (81 assertions) in 2.74s
  (3 nuovi test CheckInvoiceQueueCommandTest; 30 pre-esistenti tutti PASS)

vendor/bin/pint --test
  → PASS — {"result":"pass"}

php artisan schedule:list
  → PASS — "* * * * *  php artisan invoice:check-queue ... Next Due: 21 seconds from now"
```

### Assunzioni fatte
- [A1] `EXPLAINER_USE_LLM` assente in `.env` (default false). Il comando non usa ExplainerService — irrilevante per il task. Annotato per completezza; aggiungere `EXPLAINER_USE_LLM=true` a `.env` se si vuole abilitare le spiegazioni LLM.
- [A2] In Laravel 11, `bootstrap/app.php` (forbidden) non chiama `->withCommands()`, quindi i comandi in `app/Console/Commands/` non sono auto-scoperti. Soluzione: `ConsoleApplication::starting()` in `routes/console.php` — pattern equivalente a `->withCommands()`, idempotente, compatibile con il test framework.
- [A3] `self::FAILURE` (= 1) su AppException: il comando esce con codice 1 così che cron e scheduler possano rilevare l'errore nei propri log. Il lifecycle dello scheduler non viene interrotto.
- [A4] `Log::spy()` nei test: sostituisce il root del facade Log con un Mockery spy. Compatibile con la versione Laravel 11 / PHPUnit usata nel progetto.
- [A5] `coord/` è nei Forbidden Paths del TASK_04 — HANDOFF prodotto ugualmente come da protocollo AGENTS.md §8 (pattern P2-A accettato in tutti i task precedenti).

### Rischi / TODO residui
- P2 (DoD-2 log su file): verifica `tail -n 5 storage/logs/laravel.log | grep queue_check` richiede MySQL attivo + seeder. Non eseguibile in CI; rimandata a test manuale post-deploy.
- P2 (DoD-3 log critical): `php artisan db:seed --class=RedScenarioSeeder` richiede MySQL. Coperta dai test Unit con mock — verifica manuale in ambiente reale.
- P2 (@test deprecation): i 3 nuovi test usano `@test` doc-comment (PHPUnit 12 warning — ora 38 totali). Già tracciato nel backlog M2.
- P2 (crontab path): la sezione README indica il percorso generico `/usr/bin/php` — da adattare al VPS di destinazione con `which php`.

### Sblocca
- Nessuno — TASK_04 è l'ultimo task di Wave M2. Wave M2 completata ✅
