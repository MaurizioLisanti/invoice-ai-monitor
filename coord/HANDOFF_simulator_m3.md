## HANDOFF_simulator_m3.md

### Metadata
- task: TASK_simulator_m3
- status: DONE
- correlation_id: c4e9a2f7-1d3b-4c8e-9f0a-6b5d2e8c3a1f
- run_id: executor-v2-simulator-m3-20260303
- created: 2026-03-03T00:00:00Z
- branch: task/simulator_m3

### Summary
Implementato `invoice:simulate` — comando Artisan interattivo che simula il
flusso reale SDI in locale. Loop ogni 30s (chunk da 1s per CTRL+C reattivo),
inserisce fatture con nomi italiani realistici, processa i pending >60s con
70% accepted / 30% error e codici SDI autentici, supporta tre scenari che
producono semaforo green/yellow/red sulla dashboard.

### Comportamento per scenario

| Scenario | Inserimenti/ciclo | Processing    | Effetto semaforo   |
|----------|-------------------|---------------|--------------------|
| green    | 3-5 pending       | Sì, 70/30     | Verde (≤ 5 pending)|
| yellow   | 10-15 pending     | Sì, 70/30     | Giallo (10-25 range)|
| red      | 25-35 pending     | Sospeso       | Rosso (>50 in 2-3 cicli)|

### Dettagli implementazione
- `invoice_ref`: formato `FT-{anno}-{COGNOME}-{seq4}` con 50 cognomi italiani
- Codici SDI reali: 12 codici (00001–00413) con descrizione in italiano
- `--max-cycles=N`: opzione interna (default 0 = infinito) per test controllati
- `pcntl_signal`: registrazione SIGINT/SIGTERM con check `function_exists`
  (skip silenzioso su Windows/PHP senza estensione pcntl)
- `mt_getrandmax()` al posto del non-disponibile `MT_RAND_MAX` (fix PHP 8.4)
- Scenario `red`: processing completamente sospeso (ramo `if ($scenario !== 'red')`)

### Fix durante implementazione
- `MT_RAND_MAX` → `mt_getrandmax()`: costante non disponibile in questa build
  PHP 8.4 Herd Windows — sostituita con la funzione equivalente

### Files changed
- `app/Console/Commands/SimulateInvoicesCommand.php` — aggiunto (comando completo)
- `routes/console.php` — aggiunto `use SimulateInvoicesCommand` + `resolve()`
- `Makefile` — aggiunto target `simulate` + `simulate` in `.PHONY`
- `tests/Feature/SimulateInvoicesTest.php` — aggiunto (7 test)
- `coord/TASK_simulator_m3.md` — aggiunto (spec task)
- `coord/BOARD.md` — aggiornato (Wave M3 + hotfix post-M2)

### Commands run
```
php artisan test --stop-on-failure
  → FAIL × 1 (MT_RAND_MAX undefined) — fix applicato
  → PASS — 44/44 tests passed (109 assertions) in 6.70s

vendor/bin/pint app/Console/Commands/SimulateInvoicesCommand.php \
               routes/console.php tests/Feature/SimulateInvoicesTest.php --test
  → {"result":"pass"}
```

### Uso in produzione
```bash
# Verde — traffico normale
make simulate

# Giallo — backlog moderato (pending > 10)
make simulate SCENARIO=yellow

# Rosso — accumulo rapido (pending > 50 in ~3 cicli)
make simulate SCENARIO=red

# Oppure direttamente con artisan
php artisan invoice:simulate --scenario=red
```

### Note operative
- Il simulatore scrive direttamente su `invoices_queue` (stesso DB della dashboard).
  Avviare prima `make migrate` e mantenere MySQL/XAMPP attivo.
- In modalità `red` il processing è sospeso: la tabella cresce indefinitamente.
  Dopo il test, eseguire `make fresh` per resettare il DB.
- CTRL+C funziona su Linux/Mac via pcntl; su Windows il processo viene terminato
  dal SO con comportamento equivalente (loop si interrompe al prossimo chunk di 1s).
