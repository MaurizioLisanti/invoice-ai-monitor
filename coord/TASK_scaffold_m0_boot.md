# TASK_scaffold_m0_boot

## Metadata
- **created**: 2026-02-23T00:00:00Z
- **updated**: 2026-02-24T00:00:00Z  ← adattato per Laravel Herd (no Docker)
- **assignee**: Claude
- **status**: TODO — PRONTO (nessuna dipendenza bloccante)
- **milestone**: M0
- **risk tier**: LOW

---

## Obiettivo
Portare il progetto allo stato operativo su Laravel Herd + MySQL locale: scaffold Laravel 11 completo, `http://localhost:8000` (o `http://invoice-ai-monitor.test`) risponde HTTP 200, `make test` esegue il bootstrap PHPUnit senza errori.

---

## Ambiente
- **PHP**: 8.4 via Laravel Herd (`~/.config/herd/bin/php84/php.exe`)
- **Composer**: via Herd (`~/.config/herd/bin/composer.phar`)
- **MySQL**: XAMPP MariaDB su `127.0.0.1:3306` (root, no password)
- **Serve**: `php artisan serve` → `http://localhost:8000`
  - Oppure: muovere il repo in `~/Herd/` per `http://invoice-ai-monitor.test`

---

## Scope
- [ ] Scaffold Laravel 11: `composer create-project laravel/laravel:^11.0` (cp -rn per non sovrascrivere file progetto)
- [ ] Copiare `.env.example` → `.env`
- [ ] Generare `APP_KEY` con `php artisan key:generate`
- [ ] Verificare `.env`: `DB_HOST=127.0.0.1`, `DB_USERNAME=root`, `DB_PASSWORD=` (vuota)
- [ ] Creare database MySQL: `CREATE DATABASE IF NOT EXISTS invoice_monitor`
- [ ] `make install` → `composer install` senza errori
- [ ] `make serve` → pagina Laravel raggiungibile su `localhost:8000` → HTTP 200
- [ ] `make test` → PHPUnit si avvia (anche 0 test — nessun fatal error)

## Non-scope
- NON implementare logica di business (nessun file in `app/Services/`, `app/Models/`)
- NON modificare route o controller
- NON toccare `database/migrations/` o `database/seeders/`
- NON configurare CI/CD

---

## Allowed paths
```
.env.example
.env
composer.json
composer.lock
Makefile
```

## Forbidden paths
```
app/
database/
resources/
routes/
tests/
config/
coord/
Dockerfile
docker-compose.yml
docker/
```

---

## Dipendenze
- **BLOCKED_BY**: N/A — primo task, nessun prerequisito
- **BLOCKS**: TASK_db_m0_schema, TASK_guardrails_m1_core, TASK_tests_m1_smoke
- **Pre-check**: N/A — PRONTO

---

## Definition of Done

Tutti i comandi seguenti devono completare PASS:

```bash
# 1. Scaffold + dipendenze
make install
# → PASS se: "Application key set successfully"

# 2. Crea database
make db-create
# → PASS se: "Database pronto." (o messaggio CREATE DATABASE ok)

# 3. PHP version
/c/Users/Utente/.config/herd/bin/php84/php.exe --version
# → PASS se: PHP 8.4.x

# 4. Laravel version
/c/Users/Utente/.config/herd/bin/php84/php.exe artisan --version
# → PASS se: Laravel Framework 11.x

# 5. HTTP response (avvia serve in background, testa, poi kill)
/c/Users/Utente/.config/herd/bin/php84/php.exe artisan serve &
sleep 3 && curl -s -o /dev/null -w "%{http_code}" http://localhost:8000
# → PASS se output: 200

# 6. PHPUnit bootstrap
make test
# → PASS se PHPUnit si avvia (exit code 0 o "No tests executed")

# 7. HANDOFF prodotto
ls coord/HANDOFF_scaffold_m0_boot.md
# → PASS se il file esiste con correlation_id compilato
```

---

## Template HANDOFF da produrre

Creare `coord/HANDOFF_scaffold_m0_boot.md` con:
```
status: DONE
correlation_id: <uuid-v4>   ← OBBLIGATORIO
branch: task/scaffold_m0_boot
```

---

## Assunzioni
- [A-SC1] Laravel Herd installato e attivo (PHP 8.4 disponibile)
- [A-SC2] XAMPP MySQL in esecuzione su `127.0.0.1:3306` (utente root, nessuna password)
- [A-SC3] Connessione internet disponibile per `composer create-project`
