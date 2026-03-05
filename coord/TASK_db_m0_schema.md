# TASK_db_m0_schema

## Metadata
- **created**: 2026-02-23T00:00:00Z
- **updated**: 2026-02-23T00:00:00Z  ← completato da Planner Agent v3
- **assignee**: Qwen Coder
- **status**: BLOCKED
- **milestone**: M0
- **risk tier**: MED

---

## Obiettivo
Verificare e consolidare lo schema della tabella `invoices_queue`, rendere la migration idempotente (sicura su DB condivisi), completare il seeder con i 3 scenari di test, aggiungere gli indici di performance. Al termine: `make migrate && make seed` → PASS.

---

## Scope
- [ ] Verificare che `database/migrations/2026_02_23_000001_create_invoices_queue_table.php` sia idempotente: `if (Schema::hasTable(...)) return;` presente in `up()`
- [ ] Confermare campi minimi: `id`, `invoice_ref`, `status` (enum), `error_message`, `created_at`, `updated_at` [A5]
- [ ] Indice composito `['status', 'updated_at']` presente sulla tabella [R3]
- [ ] `InvoiceQueue::$table` letto da `config('invoice.queue_table')` (non hard-coded)
- [ ] Scope `pending()`: `where('status', 'pending')`
- [ ] Scope `errors()`: `whereIn('status', ['error', 'rejected'])`
- [ ] Scope `recent(int $limit)`: `orderByDesc('updated_at')->limit($limit)`
- [ ] `InvoiceQueueSeeder`: scenario **verde** → 5 pending, 0 errori
- [ ] `InvoiceQueueSeeder`: scenario **giallo** → 12 pending, 4 errori
- [ ] `InvoiceQueueSeeder`: scenario **rosso** → 55 pending, 11 errori
- [ ] `SEED_SCENARIO=verde make seed` PASS + contatori verificabili con SQL
- [ ] `SEED_SCENARIO=giallo make seed` PASS
- [ ] `SEED_SCENARIO=rosso make seed` PASS

## Non-scope
- NON eseguire migration sul DB di produzione (solo dev/test) [A1]
- NON modificare tabelle di sistema di Laravel (users, jobs, ecc.)
- NON aggiungere logica di business nei Model (nessun metodo oltre gli scope)
- NON toccare `app/Services/` o `app/Http/`

---

## Allowed paths
```
database/migrations/2026_02_23_000001_create_invoices_queue_table.php
database/seeders/InvoiceQueueSeeder.php
database/seeders/DatabaseSeeder.php
app/Models/InvoiceQueue.php
config/invoice.php          ← solo chiave 'queue_table'
.env                        ← solo variabile INVOICE_QUEUE_TABLE (se serve override)
```

## Forbidden paths
```
app/Services/
app/Http/
resources/
routes/
tests/
Dockerfile
docker-compose.yml
database/migrations/*       ← (tranne il file sopra elencato)
```

---

## Dipendenze
- **BLOCKED_BY**: TASK_scaffold_m0_boot (container db deve essere Up e healthy)
- **BLOCKS**: TASK_semaforo_m0_core
- **Pre-check**: TASK_scaffold_m0_boot → status DONE? **NO** → stato BLOCKED

---

## Definition of Done

```bash
# 1. Migration
docker compose exec app php artisan migrate --force
# → PASS se: "Nothing to migrate." oppure migrazione applicata senza errori

# 2. Seeder verde
docker compose exec app php artisan db:seed
# → PASS (scenario verde: 5 pending, 0 errori)

# 3. Seeder giallo (verifica soglia)
SEED_SCENARIO=giallo docker compose exec app php artisan db:seed
# → PASS

# 4. Seeder rosso (verifica soglia critica)
SEED_SCENARIO=rosso docker compose exec app php artisan db:seed
# → PASS

# 5. Verifica SQL contatori scenario rosso
docker compose exec db mysql -u invoice_user -psecret invoice_monitor \
  -e "SELECT status, COUNT(*) FROM invoices_queue GROUP BY status;"
# → PASS se pending≥55 e (error+rejected)≥11

# 6. Test suite (no regressioni)
docker compose exec app php artisan test
# → PASS

# 7. HANDOFF prodotto
ls coord/HANDOFF_db_m0_schema.md
# → PASS se esiste con correlation_id
```

---

## Comandi verifica rapida
```bash
docker compose exec app php artisan migrate:status   # Tutte DONE
docker compose exec db mysql -u invoice_user -psecret invoice_monitor \
  -e "SHOW INDEX FROM invoices_queue;"               # Indice status+updated_at presente
docker compose exec app php artisan tinker \
  --execute="echo App\Models\InvoiceQueue::pending()->count();"
```

---

## Template HANDOFF da produrre

Creare `coord/HANDOFF_db_m0_schema.md` con:
```
status: DONE
correlation_id: <uuid-v4>   ← OBBLIGATORIO
branch: task/db_m0_schema
```

---

## Assunzioni
- [A1] Tabella `invoices_queue` esiste già in produzione — migration NON va eseguita sul DB prod
- [A5] Schema assunto (id, invoice_ref, status, error_message, timestamps) — se schema reale differisce: creare VIEW SQL read-only e impostare `INVOICE_QUEUE_TABLE=nome_view` nel `.env` prod [R1 — ALTA PRIORITÀ]
