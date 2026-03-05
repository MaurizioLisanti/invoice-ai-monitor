# TASK_docs_m1_runbook

## Metadata
- **created**: 2026-02-23T00:00:00Z
- **updated**: 2026-02-23T00:00:00Z  ← completato da Planner Agent v3
- **assignee**: Claude
- **status**: BLOCKED
- **milestone**: M1
- **risk tier**: LOW

---

## Obiettivo
Portare il `README.md` a documentazione completa e operativa: quick start testato, tabella soglie, troubleshooting per l'utente non-tecnico (Mario), runbook "semaforo rosso alle 9:00". Aggiornare `SPEC.md` milestone M0 → DONE e `BOARD.md` task board.

---

## Scope

### README.md
- [ ] Sezione **Quick start**: 5 comandi che portano da zero a `http://localhost:8080`
- [ ] Sezione **Scenari di test**: `SEED_SCENARIO=verde|giallo|rosso make fresh`
- [ ] Sezione **Comandi Makefile**: tabella comando → descrizione → output atteso
- [ ] Sezione **Soglie semaforo**: tabella con soglie verde/giallo/rosso e come cambiarle in `.env`
- [ ] Sezione **Troubleshooting**:
  - [ ] "Semaforo non si aggiorna" → causa: CACHE_TTL troppo alto; soluzione: ridurre `CACHE_TTL_SECONDS`
  - [ ] "DB non raggiungibile" → causa: container `db` non healthy; soluzione: `docker compose restart db`
  - [ ] "HTTP 503 su /status" → causa: errore query MySQL; soluzione: verificare log `make logs`
- [ ] Sezione **Runbook — semaforo rosso** (per Mario, non-tecnico):
  - [ ] Step 1: non farsi prendere dal panico
  - [ ] Step 2: cliccare "Spiegami" e leggere il messaggio
  - [ ] Step 3: se dice "Contatta il supporto tecnico" → chiamare [numero/email sviluppatore]
  - [ ] Step 4: screenshot della dashboard e inviarlo al supporto
- [ ] Sezione **Schema MySQL da verificare** [R1, A5]: istruzioni per mappare schema reale
- [ ] Sezione **Aggiungere autenticazione post-MVP** [R4, A2]: riferimento a Laravel Sanctum/Basic Auth

### SPEC.md
- [ ] Aggiornare milestone M0: `[ ]` → `[x] DONE` per tutti i criteri soddisfatti
- [ ] Aggiungere data di completamento M0
- [ ] Aggiungere sezione **Milestone M1** con acceptance criteria PASS/FAIL:
  - [ ] Error handling: HTTP 503 su errore DB
  - [ ] Log JSON: output verificabile con `cat storage/logs/laravel.log | python3 -c ...`
  - [ ] Test suite: `make test` PASS su macchina pulita
  - [ ] Runbook: quick start funzionante da README

### coord/BOARD.md
- [ ] Aggiornare colonna **Status** di ogni task completato → `DONE`
- [ ] Aggiungere data completamento nel campo task

## Non-scope
- NON creare documentazione API formale (no OpenAPI/Swagger nell'MVP)
- NON aggiungere CHANGELOG o versioning
- NON documentare funzionalità non ancora implementate (no futurismo)
- NON modificare codice applicativo

---

## Allowed paths
```
README.md
SPEC.md                              ← solo sezioni Milestones
coord/BOARD.md                       ← solo aggiornamento Status colonna
```

## Forbidden paths
```
app/
database/
resources/
routes/
config/
tests/
Dockerfile
docker-compose.yml
Makefile
coord/TASK_*.md                      ← non modificare altri task
coord/AGENTS.md
```

---

## Dipendenze
- **BLOCKED_BY**: TASK_guardrails_m1_core (runbook deve documentare comportamento errore 503)
- **BLOCKED_BY**: TASK_tests_m1_smoke (SPEC.md milestone M0 aggiornabile solo se test PASS)
- **BLOCKS**: (nessuno — ultimo task della wave)
- **Pre-check**:
  - TASK_guardrails_m1_core → status DONE? **NO** → stato BLOCKED
  - TASK_tests_m1_smoke → status DONE? **NO** → stato BLOCKED

---

## Definition of Done

```bash
# 1. README minimo 80 righe
cat README.md | wc -l
# → PASS se ≥ 80

# 2. Quick start funzionante (da macchina pulita)
make install && make up && make migrate && make seed
curl -s -o /dev/null -w "%{http_code}" http://localhost:8080
# → PASS se 200

# 3. SPEC.md ha milestone M0 marcata come completata
grep -c "\[x\]" SPEC.md
# → PASS se ≥ 1

# 4. BOARD.md ha almeno un task marcato DONE
grep -c "DONE" coord/BOARD.md
# → PASS se ≥ 1

# 5. Runbook leggibile da non-tecnico
# → PASS (verifica manuale): un utente non-tecnico riesce a seguire
#    la procedura "semaforo rosso" senza assistenza

# 6. HANDOFF prodotto
ls coord/HANDOFF_docs_m1_runbook.md
# → PASS se esiste con correlation_id
```

---

## Template HANDOFF da produrre

Creare `coord/HANDOFF_docs_m1_runbook.md` con:
```
status: DONE
correlation_id: <uuid-v4>   ← OBBLIGATORIO
branch: task/docs_m1_runbook
```

---

## Assunzioni
- [A-DOC1] "Mario" (responsabile amministrativo) non ha accesso diretto al terminale — il runbook deve usare solo interfaccia browser
- [A-DOC2] Il contatto del supporto tecnico (`[numero/email sviluppatore]`) sarà inserito dal team prima del deploy
