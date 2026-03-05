# TASK_fix_phpunit_attributes_m2

## Metadata
- **created**: 2026-03-03T00:00:00Z
- **updated**: 2026-03-03T00:00:00Z
- **assignee**: Claude
- **status**: DONE ✅ APPROVED ✅
- **milestone**: M2 backlog
- **risk tier**: LOW

---

## Obiettivo
Migrare tutti i doc-comment `/** @test */` all'attributo nativo PHP 8 `#[Test]`
in tutti i file di test del progetto. PHPUnit 11 depreca la notazione doc-comment
(@test annotation) in favore degli attributi PHP 8 (#[Test]). Obiettivo: zero warning
PHPUnit 11 deprecation, suite verde al 100%.

---

## Scope
- [ ] In ogni file di test (6 file), aggiungere `use PHPUnit\Framework\Attributes\Test;`
- [ ] Sostituire ogni `/** @test */` con `#[Test]` (31 occorrenze totali)
- [ ] Pint PASS, test PASS, HANDOFF prodotto

## Non-scope
- NON modificare la logica dei test
- NON modificare file sorgente (app/, routes/, config/)
- NON aggiungere o rimuovere metodi di test
- NON rinominare metodi

---

## Allowed paths
```
tests/Feature/DashboardTest.php
tests/Feature/ChatTest.php
tests/Feature/LogViewerTest.php
tests/Unit/TrafficLightServiceTest.php
tests/Unit/ExplainerServiceTest.php
tests/Unit/CheckInvoiceQueueCommandTest.php
```

## Forbidden paths
```
app/
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
- **BLOCKED_BY**: — (nessuna)
- **BLOCKS**: TASK_index_graceful_m2 (per avere il test nuovo già con #[Test])
- **PARALLEL con**: nessuno

---

## Definition of Done

```bash
# 1. Test suite PASS (nessuna regressione)
php artisan test --stop-on-failure
# → PASS ≥ 33/33, 0 failure

# 2. Linter
vendor/bin/pint --test
# → PASS {"result":"pass"}

# 3. Nessun @test rimasto
grep -r "@test" tests/
# → nessun output (0 occorrenze)

# 4. HANDOFF prodotto
ls coord/HANDOFF_fix_phpunit_attributes_m2.md
# → PASS se esiste con correlation_id
```
