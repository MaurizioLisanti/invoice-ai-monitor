# TASK_simulator_m3

## Metadata
- **created**: 2026-03-03T00:00:00Z
- **updated**: 2026-03-03T00:00:00Z
- **assignee**: Claude
- **status**: TODO
- **milestone**: M3
- **risk tier**: MED

---

## Obiettivo
Implementare il comando Artisan `invoice:simulate` che simula il flusso reale
della coda fatture SDI in locale. Il comando gira in loop ogni 30 secondi,
inserisce nuove fatture con nomi italiani realistici, processa le pending
più vecchie di 60 secondi (70% accepted / 30% error con codici SDI reali),
e supporta tre scenari (green/yellow/red) per controllare il comportamento
del semaforo. Si ferma con CTRL+C.

---

## Scope

- [ ] Creare `app/Console/Commands/SimulateInvoicesCommand.php`:
  - **Signature**: `invoice:simulate`
  - **Opzione** `--scenario=green` (valori: `green`, `yellow`, `red`)
  - **Opzione interna** `--max-cycles=0` (0 = infinito; N > 0 usato nei test)
  - **Loop 30s**: sleep a chunk di 1s per rispondere rapidamente a CTRL+C
  - **Inserisce 3-5 fatture** per ciclo (scenario green),
    10-15 (yellow), 25-35 (red)
  - **`invoice_ref`**: formato `FT-{anno}-{COGNOME}-{seq4}` con cognomi italiani
    realistici (lista ≥ 40 cognomi embedded nella costante `RAGIONI_SOCIALI`)
  - **Processa pending > 60s**: 70% → `accepted`, 30% → `error`
    con codice SDI reale in `error_message` (≥ 10 codici reali)
  - **Scenario red**: salta interamente la fase di processing (accumulo rapido)
  - **Signal handling**: `pcntl_signal(SIGINT/SIGTERM)` se estensione disponibile
    (skip silenzioso su Windows senza pcntl)
  - **Log per ciclo**: `Log::info('simulator_cycle', [cycle, scenario, inserted,
    accepted, errors, ts])`
  - **Costante** `private const int MAX_ATTEMPTS` → NO
    (questa è ChatService); qui usare `LOOP_SECONDS = 30`, `ACCEPTED_RATE = 0.70`

- [ ] Registrare il comando in `routes/console.php` con `ConsoleApplication::starting()`

- [ ] Aggiungere target `simulate` nel `Makefile`:
  ```makefile
  simulate:
      $(PHP) artisan invoice:simulate --scenario=$(or $(SCENARIO),green)
  ```

- [ ] Creare `tests/Feature/SimulateInvoicesTest.php` con ≥ 5 test:
  1. Ciclo green inserisce 3-5 righe in DB
  2. Pending >60s vengono processati (accepted o error) in scenario green
  3. Scenario red non processa i pending (accumulo)
  4. Scenario non valido → exit code 1
  5. Log `simulator_cycle` emesso ad ogni ciclo

- [ ] Pint PASS, test PASS (tutti i test pre-esistenti inclusi), HANDOFF prodotto

## Non-scope
- NON modificare TrafficLightService, InvoiceQueue model, migration
- NON aggiungere schedule (è un comando interattivo, non periodico)
- NON inviare notifiche esterne
- NON aggiungere nuovi package composer
- NON modificare la dashboard o le route HTTP

---

## Allowed paths
```
app/Console/Commands/SimulateInvoicesCommand.php   ← nuovo file
routes/console.php                                 ← add resolve()
Makefile                                           ← add simulate target
tests/Feature/SimulateInvoicesTest.php             ← nuovo file test
coord/HANDOFF_simulator_m3.md                      ← da produrre
```

## Forbidden paths
```
app/Services/
app/Http/
app/Models/
resources/
database/migrations/
database/seeders/
config/
bootstrap/app.php
```

---

## Dipendenze
- **BLOCKED_BY**: chat_retry_fix ✅ (hotfix applicato 2026-03-03)
- **BLOCKS**: —
- **PARALLEL con**: nessuno

---

## Definition of Done

```bash
# 1. Comando eseguibile — 1 ciclo e poi esce
php artisan invoice:simulate --scenario=green --max-cycles=1
# → exit 0, output mostra "+ N fatture inserite"

# 2. Scenario non valido → exit 1
php artisan invoice:simulate --scenario=wrong
# → exit 1, messaggio errore visibile

# 3. Fatture inserite nel DB (dopo migrate+seed verde)
php artisan invoice:simulate --scenario=green --max-cycles=1
# → SELECT COUNT(*) FROM invoices_queue WHERE status='pending' > 0

# 4. Pending processati dopo 60s
# (verificato nei test Feature via RefreshDatabase + timestamp passato)

# 5. Test suite PASS
php artisan test --stop-on-failure
# → PASS (≥ 42 test totali, 0 failure)

# 6. Pint PASS
vendor/bin/pint --test
# → {"result":"pass"}

# 7. HANDOFF
ls coord/HANDOFF_simulator_m3.md
# → file esiste con correlation_id
```

---

## Codici SDI reali da usare (almeno 10)

| Codice | Descrizione |
|--------|-------------|
| 00001  | File vuoto o non leggibile |
| 00002  | File non integro — firma digitale non valida |
| 00101  | Formato fattura non conforme alle specifiche FatturaPA |
| 00102  | File non corrisponde a nessuno schema XML previsto |
| 00103  | Identificativo SDI duplicato — fattura già ricevuta |
| 00201  | Codice fiscale cedente/prestatore non trovato in Anagrafica Tributaria |
| 00202  | Partita IVA cedente/prestatore non trovata |
| 00301  | Codice fiscale cessionario/committente non trovato |
| 00400  | Data fattura successiva alla data di ricezione |
| 00401  | Importo totale incongruente con la somma delle righe |
| 00411  | Numero fattura non univoco per lo stesso cedente |
| 00413  | Regime fiscale non presente nella tabella di riferimento |

---

## Template HANDOFF da produrre

```
coord/HANDOFF_simulator_m3.md
  status: DONE
  correlation_id: <uuid-v4>
  branch: task/simulator_m3
```
