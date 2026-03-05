# TASK_dashboard_m0_view

## Metadata
- **created**: 2026-02-23T00:00:00Z
- **updated**: 2026-02-24T16:10:00Z
- **assignee**: Claude
- **status**: DONE ✅
- **milestone**: M0
- **risk tier**: LOW

---

## Obiettivo
Collegare i servizi alla UI: `DashboardController` espone index / status / explain; la view Blade mostra semaforo, tabella eventi e pulsante "Spiegami"; il JS polling aggiorna il semaforo ogni N secondi senza ricaricare la pagina. Al termine: tutti i test `DashboardTest` → PASS e semaforo visivamente corretto nei 3 scenari.

---

## Scope

### DashboardController
- [ ] `__construct()`: inject `TrafficLightService` e `ExplainerService` come readonly
- [ ] `index()`: ritorna `view('dashboard', [snapshot, events, refreshSeconds])`
- [ ] `status()`: `response()->json($this->cachedSnapshot())`
- [ ] `explain()`: `response()->json([status, explanation])`
- [ ] `cachedSnapshot()`: `Cache::remember('semaforo_snapshot', $ttl, fn())`
- [ ] Nessuna logica di business nel controller (delegato ai Service)

### Routes (routes/web.php)
- [ ] `GET /` → `DashboardController@index` → `name('dashboard')`
- [ ] `GET /status` → `DashboardController@status` → `name('dashboard.status')`
- [ ] `GET /explain` → `DashboardController@explain` → `name('dashboard.explain')`
- [ ] Nessuna route POST/PUT/DELETE/PATCH

### View Blade (resources/views/dashboard.blade.php)
- [ ] Semaforo: 3 luci CSS (`light-red`, `light-yellow`, `light-green`), solo quella attiva ha `class="active"`
- [ ] Contatori: `#cnt-pending` e `#cnt-errors` aggiornabili via JS
- [ ] Tabella `invoices_queue`: colonne `invoice_ref`, `status` (pill colorata), `error_message`, `updated_at`
- [ ] Pulsante "Spiegami": fetch `GET /explain` → mostra testo in `#spiegami-box`
- [ ] JS `fetchStatus()`: fetch `GET /status` → aggiorna semaforo, contatori, `#last-updated`
- [ ] `setInterval(fetchStatus, REFRESH_MS)` con `REFRESH_MS = {{ $refreshSeconds * 1000 }}`
- [ ] Nessuna dipendenza da CDN o librerie JS esterne
- [ ] Responsive base (max-width 960px, system fonts)

## Non-scope
- NON implementare autenticazione o middleware [A2]
- NON usare Livewire o Alpine.js (vanilla JS sufficiente per MVP)
- NON aggiungere CSS framework (Tailwind, Bootstrap)
- NON aggiungere paginazione alla tabella (ultimi 20, non configurabile nell'MVP)
- NON implementare CSRF protection sulle route GET (read-only)

---

## Allowed paths
```
app/Http/Controllers/DashboardController.php
resources/views/dashboard.blade.php
routes/web.php
public/                              ← solo se asset statici necessari (favicon, ecc.)
```

## Forbidden paths
```
app/Services/                        ← usa come dipendenza, NON modifica
app/Models/                          ← usa come dipendenza, NON modifica
config/invoice.php                   ← solo lettura
database/
tests/                               ← nessun test aggiunto qui
Dockerfile
docker-compose.yml
coord/
```

---

## Dipendenze
- **BLOCKED_BY**: TASK_semaforo_m0_core (TrafficLightService e ExplainerService devono esistere)
- **BLOCKS**: (nessuno — last task M0)
- **Pre-check**: TASK_semaforo_m0_core → status DONE? **NO** → stato BLOCKED

---

## Definition of Done

```bash
# 1. Test controller e view
docker compose exec app php artisan test --filter=DashboardTest --stop-on-failure
# → PASS (tutti i test: verde/giallo/rosso, HTTP 200, /status JSON, /explain testo)

# 2. Verifica HTTP manuale
curl -s -o /dev/null -w "%{http_code}" http://localhost:8080
# → 200

# 3. Verifica endpoint /status
curl -s http://localhost:8080/status | grep -E '"status":"(green|yellow|red)"'
# → PASS (match trovato)

# 4. Verifica endpoint /explain
curl -s http://localhost:8080/explain | grep '"explanation"'
# → PASS (campo presente)

# 5. Linter
docker compose exec app vendor/bin/pint --test
# → PASS

# 6. Test completo (no regressioni)
docker compose exec app php artisan test --stop-on-failure
# → PASS

# 7. Verifica scenari semaforo (manuale — 3 scenari)
# SEED_SCENARIO=verde  make fresh → aprire http://localhost:8080 → semaforo verde
# SEED_SCENARIO=giallo make fresh → aprire http://localhost:8080 → semaforo giallo
# SEED_SCENARIO=rosso  make fresh → aprire http://localhost:8080 → semaforo rosso

# 8. HANDOFF prodotto
ls coord/HANDOFF_dashboard_m0_view.md
# → PASS se esiste con correlation_id
```

---

## Contratto I/O endpoint

### GET /status → JSON
```json
{
  "status": "yellow",
  "pending": 12,
  "errors": 4,
  "last_updated": "2026-02-23T09:05:00+01:00"
}
```

### GET /explain → JSON
```json
{
  "status": "red",
  "explanation": "PROBLEMA CRITICO: 55 fatture bloccate in attesa di invio e 11 fatture in errore o rifiutate. Il sistema di fatturazione potrebbe essere bloccato o in errore grave. Contatta immediatamente il supporto tecnico. Non attendere."
}
```

### Codici HTTP
| Endpoint  | OK  | Errore DB   |
|-----------|-----|-------------|
| GET /     | 200 | 500 (blade) |
| GET /status | 200 | 503 (post guardrails) |
| GET /explain | 200 | 503 (post guardrails) |

---

## Template HANDOFF da produrre

Creare `coord/HANDOFF_dashboard_m0_view.md` con:
```
status: DONE
correlation_id: <uuid-v4>   ← OBBLIGATORIO
branch: task/dashboard_m0_view
```

---

## Assunzioni
- [A2] Nessuna autenticazione — accesso protetto solo da rete aziendale/VPN
- [A6] `DASHBOARD_REFRESH_SECONDS=60` configurabile via `.env` senza modifica al codice
