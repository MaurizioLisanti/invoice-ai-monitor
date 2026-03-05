# TASK_index_graceful_m2

## Metadata
- **created**: 2026-03-03T00:00:00Z
- **updated**: 2026-03-03T00:00:00Z
- **assignee**: Claude
- **status**: DONE ✅ APPROVED ✅
- **milestone**: M2 backlog
- **risk tier**: LOW

---

## Obiettivo
Aggiungere graceful degradation a `DashboardController::index()`: quando MySQL non è
raggiungibile (AppException lanciata da TrafficLightService), la homepage deve rispondere
HTTP 503 invece di crashare con un 500 non gestito. Pattern coerente con `status()` e
`explain()` già implementati.

---

## Scope
- [ ] In `DashboardController::index()`, avvolgere `cachedSnapshot()` e
  `recentEvents()` in `try/catch (AppException)` → `abort(503)` su errore
- [ ] Aggiungere in `tests/Feature/DashboardTest.php` un test:
  `dashboard_index_restituisce_503_se_db_non_disponibile`
  che mocca TrafficLightService→snapshot() per lanciare AppException e asserisce
  `$this->get('/')->assertStatus(503)`
- [ ] Pint PASS, test PASS, HANDOFF prodotto

## Non-scope
- NON modificare `status()` o `explain()` (già gestiscono AppException)
- NON creare nuove view o template di errore
- NON modificare TrafficLightService o ExplainerService
- NON aggiungere logging aggiuntivo (già gestito dal gestore eccezioni Laravel)

---

## Allowed paths
```
app/Http/Controllers/DashboardController.php
tests/Feature/DashboardTest.php
```

## Forbidden paths
```
app/Services/
app/Exceptions/
routes/
config/
resources/
database/
Makefile
README.md
composer.json
coord/          ← solo HANDOFF consentito
```

---

## Dipendenze
- **BLOCKED_BY**: TASK_fix_phpunit_attributes_m2 (il nuovo test usa #[Test])
- **BLOCKS**: — (nessuno)
- **PARALLEL con**: nessuno

---

## Definition of Done

```bash
# 1. Test suite PASS (33+1 = 34 test)
php artisan test --stop-on-failure
# → PASS ≥ 34/34, 0 failure

# 2. Nuovo test presente e verde
php artisan test --filter dashboard_index_restituisce_503_se_db_non_disponibile
# → PASS (1 test, exit 0)

# 3. Linter
vendor/bin/pint --test
# → PASS {"result":"pass"}

# 4. HANDOFF prodotto
ls coord/HANDOFF_index_graceful_m2.md
# → PASS se esiste con correlation_id
```
