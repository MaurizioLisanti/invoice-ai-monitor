# TASK_04_scheduler_m2

## Metadata
- **created**: 2026-03-02T00:00:00Z
- **updated**: 2026-03-03T00:00:00Z
- **assignee**: Claude
- **status**: DONE ✅ APPROVED ✅
- **milestone**: M2
- **risk tier**: MED

---

## Obiettivo
Implementare il comando Artisan `invoice:check-queue` schedulato ogni 60 secondi. Ad ogni ciclo: legge snapshot coda, scrive log strutturato con stato semaforo. Se lo stato diventa `red`, scrive un log di livello `critical` (alert passivo per sviluppatore). Nessuna notifica esterna nell'MVP.

---

## Scope
- [ ] Creare `app/Console/Commands/CheckInvoiceQueueCommand.php`:
  - Signature: `invoice:check-queue`
  - Description: "Controlla lo stato della coda fatture e registra il risultato nel log"
  - Usa `TrafficLightService::snapshot()` avvolto in `try/catch AppException`
  - Log obbligatorio ad ogni esecuzione: `Log::info('queue_check', ['status'=>..., 'pending'=>..., 'errors'=>..., 'ts'=>...])`
  - Se `status === 'red'`: `Log::critical('queue_alert_red', ['pending'=>..., 'errors'=>..., 'ts'=>...])`
- [ ] Registrare schedule in `routes/console.php` (Laravel 11 style): `Schedule::command('invoice:check-queue')->everyMinute()`
- [ ] Aggiungere target `cron` nel `Makefile`: `php artisan schedule:work`
- [ ] Aggiornare `README.md`: sezione "Scheduler" con istruzioni per avvio locale (`make cron`) e produzione (cron di sistema: `* * * * * php /path/to/artisan schedule:run`)
- [ ] Creare `tests/Unit/CheckInvoiceQueueCommandTest.php`: mock `TrafficLightService`, verificare log emesso e codice di uscita 0
- [ ] Pint PASS, test PASS, HANDOFF prodotto

## Non-scope
- NON implementare notifiche esterne (email, Slack, webhook)
- NON implementare storico stato su DB (nessuna tabella aggiuntiva)
- NON modificare l'intervallo a runtime — solo via config
- NON aggiungere lock distribuito (single-server MVP)
- NON modificare `TrafficLightService` o `ExplainerService`

---

## Allowed paths
```
app/Console/Commands/CheckInvoiceQueueCommand.php  ← nuovo file
routes/console.php
Makefile
README.md                                          ← sezione "Scheduler"
tests/Unit/CheckInvoiceQueueCommandTest.php        ← nuovo file test
```

## Forbidden paths
```
app/Services/                                      ← solo lettura, NON modificare
app/Http/
resources/
database/
config/
bootstrap/app.php
Dockerfile
docker-compose.yml
coord/
```

---

## Dipendenze
- **BLOCKED_BY**: TASK_03_spiegami_dynamic_m2
- **BLOCKS**: — (ultimo task Wave M2)
- **PARALLEL con**: nessuno

---

## Definition of Done

```bash
# 1. Comando eseguibile manualmente senza errori
php artisan invoice:check-queue
# → PASS se exit code 0 e nessuna eccezione non gestita

# 2. Log strutturato presente dopo esecuzione
tail -n 5 storage/logs/laravel.log | grep "queue_check"
# → PASS se riga JSON con campi: status, pending, errors, ts

# 3. Log critical in caso di stato red (seed scenario rosso attivo)
php artisan db:seed --class=RedScenarioSeeder && php artisan invoice:check-queue
tail -n 10 storage/logs/laravel.log | grep "queue_alert_red"
# → PASS se riga JSON critical presente

# 4. Schedule registrato
php artisan schedule:list
# → PASS se "invoice:check-queue" appare con frequenza "Every minute"

# 5. Test suite PASS (command test con mock TrafficLightService)
php artisan test --stop-on-failure
# → PASS (≥ 19 test, 0 failure)

# 6. Linter
vendor/bin/pint --test
# → PASS {"result":"pass"}

# 7. HANDOFF prodotto
ls coord/HANDOFF_scheduler_m2.md
# → PASS se esiste con correlation_id
```

---

## Formato log atteso

```json
// Log normale (ogni minuto)
{
  "message": "queue_check",
  "context": {
    "status": "yellow",
    "pending": 15,
    "errors": 2,
    "ts": "2026-03-02T10:00:00+01:00"
  },
  "level": 200,
  "level_name": "INFO",
  "channel": "local",
  "datetime": "2026-03-02T10:00:00.000000+01:00"
}

// Alert (quando status = red)
{
  "message": "queue_alert_red",
  "context": {
    "pending": 55,
    "errors": 12,
    "ts": "2026-03-02T10:01:00+01:00"
  },
  "level": 500,
  "level_name": "CRITICAL",
  ...
}
```

---

## Template HANDOFF da produrre

Creare `coord/HANDOFF_scheduler_m2.md` con:
```
status: DONE
correlation_id: <uuid-v4>   ← OBBLIGATORIO
branch: task/scheduler_m2
```
