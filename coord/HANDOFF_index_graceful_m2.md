## HANDOFF_index_graceful_m2.md

### Metadata
- task: TASK_index_graceful_m2
- status: DONE
- correlation_id: c9e1a3f7-5b2d-4e8c-a0f6-3d7b9e2c1a5f
- run_id: executor-v2-index-graceful-m2-20260303
- created: 2026-03-03T00:00:00Z
- branch: task/index_graceful_m2

### Summary
Aggiunto `try/catch (AppException)` in `DashboardController::index()`: se MySQL non è
raggiungibile la homepage restituisce HTTP 503 (abort) invece di crashare con 500.
Pattern coerente con `status()` e `explain()` già implementati. Aggiunto 1 test Feature
`dashboard_index_restituisce_503_se_db_non_disponibile` con mock di TrafficLightService.
Suite finale: 34/34 PASS (82 assertions). Pint PASS.

### Files changed
- `app/Http/Controllers/DashboardController.php` — modificato (try/catch AppException in index())
- `tests/Feature/DashboardTest.php` — modificato (nuovo test graceful 503 + use AppException/TrafficLightService)

### Commands run
```
php artisan test --stop-on-failure
  → PASS — 34/34 tests passed (82 assertions) in 2.16s
  (1 nuovo test: dashboard_index_restituisce_503_se_db_non_disponibile PASS)

php artisan test --filter dashboard_index_restituisce_503_se_db_non_disponibile
  → PASS — 1/1 test passed

vendor/bin/pint --test
  → PASS — {"result":"pass"}
```

### Assunzioni fatte
- [A1] `abort(503)` è idiomatic Laravel — lancia HttpException gestita dal framework,
  restituisce risposta HTTP 503 senza richiedere view dedicata. Coerente con il progetto.
- [A2] Il `catch (AppException)` non cattura `\Throwable` generici — intenzionale:
  gli errori non-dominio (es. errori PHP interni) vanno al gestore di default di Laravel.
- [A3] La cache (array store in test) non maschera l'eccezione: `Cache::remember` propaga
  le eccezioni lanciate dal callback senza swallowing — verificato comportamento Laravel 11.
- [A4] Il test usa `#[Test]` (già migrato da TASK_fix_phpunit_attributes_m2).

### Rischi / TODO residui
- P2 (return type): la firma `public function index(): View` è tecnicamente imprecisa
  (abort lancia un'eccezione, non "non ritorna View"). Pint non segnala questo — è
  competenza di PHPStan/Psalm. Non bloccante per MVP.
- P2: README non aggiornato con la nota graceful 503. Già documentato in SPEC.md M2 [M2].

### Sblocca
- Nessuno — tutti i task backlog M2 completati.
