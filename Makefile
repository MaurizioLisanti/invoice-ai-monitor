.PHONY: install _laravel_scaffold db-create serve migrate seed fresh test lint fmt logs clear cron simulate

# ── PHP e Composer — percorsi Laravel Herd (Windows) ───────────────
# Herd installa PHP in ~/.config/herd/bin/php84/.
# Se "php" è già in PATH (CMD aperta da Herd o PATH utente aggiornato),
# sovrascrivere le due righe seguenti con:  PHP := php  |  COMPOSER_CMD := composer
PHP          := /c/Users/Utente/.config/herd/bin/php84/php.exe
COMPOSER_CMD := $(PHP) /c/Users/Utente/.config/herd/bin/composer.phar

# ── Setup (prima installazione) ────────────────────────────────────
# Scaffolda Laravel 11, installa dipendenze, genera APP_KEY.
# Eseguire UNA SOLA VOLTA dopo il clone del repo.
install:
	@test -f .env || cp .env.example .env
	@[ -f artisan ] || $(MAKE) _laravel_scaffold
	$(COMPOSER_CMD) install --no-interaction --prefer-dist
	$(PHP) artisan key:generate --ansi
	@echo ""
	@echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
	@echo "✓ Setup completato."
	@echo "  1. Crea DB MySQL:  make db-create"
	@echo "  2. Migra schema:   make migrate"
	@echo "  3. Seed scenario:  make seed"
	@echo "  4a. Herd serve:    sposta il repo in %USERPROFILE%\\Herd\\"
	@echo "      → http://invoice-ai-monitor.test"
	@echo "  4b. artisan serve: make serve  →  http://localhost:8000"
	@echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Scaffolding Laravel 11 in-place (senza Docker).
# Crea il progetto in una cartella temp, poi copia i file base
# senza sovrascrivere i file già presenti nel repo.
_laravel_scaffold:
	@echo "Prima installazione: scaffolding Laravel 11..."
	$(COMPOSER_CMD) create-project "laravel/laravel:^11.0" /tmp/lbase_invoice \
		--prefer-dist --no-interaction --no-scripts --no-plugins
	cp -rn /tmp/lbase_invoice/. .
	rm -rf /tmp/lbase_invoice
	@echo "✓ Scaffold Laravel 11 creato (file esistenti non sovrascritti)."

# ── Database ────────────────────────────────────────────────────────
# Crea il database se non esiste (richiede MySQL in PATH o path XAMPP).
db-create:
	@echo "Creazione database invoice_monitor..."
	@/c/xampp/mysql/bin/mysql.exe -u root \
		-e "CREATE DATABASE IF NOT EXISTS invoice_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
		&& echo "✓ Database pronto." \
		|| echo "⚠  Crea manualmente: CREATE DATABASE invoice_monitor CHARACTER SET utf8mb4;"

migrate:
	$(PHP) artisan migrate --force

seed:
	$(PHP) artisan db:seed

fresh:
	$(PHP) artisan migrate:fresh --seed
	@echo "▶ DB resettato — scenario: $${SEED_SCENARIO:-verde}"

# ── Sviluppo locale ─────────────────────────────────────────────────
serve:
	@echo "▶ Dashboard: http://localhost:8000"
	$(PHP) artisan serve

# ── Qualità ─────────────────────────────────────────────────────────
test:
	$(PHP) artisan test --stop-on-failure

lint:
	$(PHP) vendor/bin/pint --test

fmt:
	$(PHP) vendor/bin/pint

# ── Utility ─────────────────────────────────────────────────────────
logs:
	tail -f storage/logs/laravel.log

clear:
	$(PHP) artisan config:clear
	$(PHP) artisan cache:clear
	$(PHP) artisan route:clear
	$(PHP) artisan view:clear

# ── Scheduler ───────────────────────────────────────────────────────
# Avvia lo scheduler in foreground (sviluppo locale).
# In produzione usare il cron di sistema: * * * * * php /path/to/artisan schedule:run
cron:
	$(PHP) artisan schedule:work

# ── Simulatore coda SDI ─────────────────────────────────────────────
# Avvia il simulatore interattivo (CTRL+C per fermare).
# Scenari: green (default) | yellow | red
# Esempio: make simulate SCENARIO=red
simulate:
	$(PHP) artisan invoice:simulate --scenario=$(or $(SCENARIO),green)
