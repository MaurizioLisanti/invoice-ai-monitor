# TASK_03_spiegami_dynamic_m2

## Metadata
- **created**: 2026-03-02T00:00:00Z
- **updated**: 2026-03-02T00:00:00Z
- **assignee**: Claude
- **status**: BLOCKED
- **milestone**: M2
- **risk tier**: MED

---

## Obiettivo
Sostituire i template statici di `ExplainerService` con risposte dinamiche generate da Claude (modello configurato in `config/ai.php`). La spiegazione diventa contestuale: include il numero esatto di fatture, i messaggi di errore recenti, il trend. Tono: italiano plain-language per responsabile amministrativo non-tecnico.

Deve mantenere invariante il criterio M0-05 (nessun termine tecnico nella risposta).

> Dipende da TASK_02: riusa `config/ai.php` e il pattern di chiamata API già introdotto da `ChatService`.

---

## Scope
- [ ] Aggiungere flag `EXPLAINER_USE_LLM=true` in `.env.example` e `config/invoice.php`
- [ ] Refactor `app/Services/ExplainerService.php`:
  - Se `EXPLAINER_USE_LLM=true`: genera spiegazione via chiamata LLM (stessa infrastruttura di `ChatService`)
  - Se `EXPLAINER_USE_LLM=false` o API non disponibile: fallback ai template statici M0 (graceful degradation)
- [ ] Prompt LLM: contesto snapshot (stato, pending, errors, ultimi messaggi errore) + istruzione "spiega in italiano per non-tecnico, max 3 frasi, NO termini tecnici"
- [ ] Aggiornare `tests/Unit/ExplainerServiceTest.php`: mock del client LLM, verificare che la risposta sia non-vuota e non contenga termini tecnici vietati
- [ ] Firma pubblica `explain(array $snapshot): string` rimane identica (nessuna modifica al controller)
- [ ] Pint PASS, test PASS (mock), HANDOFF prodotto

## Non-scope
- NON cambiare la firma pubblica di `ExplainerService::explain()` — il controller non deve cambiare
- NON modificare la view dashboard (l'output è già visualizzato nel componente Spiegami)
- NON aggiungere caching della risposta LLM
- NON modificare `ChatService` (solo lettura come riferimento architetturale)

---

## Allowed paths
```
app/Services/ExplainerService.php
config/invoice.php
tests/Unit/ExplainerServiceTest.php
.env.example                            ← aggiunta EXPLAINER_USE_LLM
```

## Forbidden paths
```
app/Services/TrafficLightService.php
app/Services/ChatService.php            ← solo lettura (non modificare)
app/Services/LogReaderService.php
app/Http/Controllers/
resources/views/
routes/
database/
bootstrap/
coord/
```

---

## Dipendenze
- **BLOCKED_BY**: TASK_02_chat_ai_m2 (riusa config/ai.php e il pattern Anthropic SDK)
- **BLOCKS**: TASK_04_scheduler_m2
- **PARALLEL con**: nessuno

---

## Verifica invariante SPEC M0-05

Il criterio M0-05 (spiegazione in italiano, no termini tecnici) deve restare PASS:

```bash
# Termini vietati nella risposta LLM:
VIETATI="HTTP|SQL|exception|stack trace|PDO|query|database|cache|Laravel"

RISPOSTA=$(curl -s http://localhost:8000/explain | jq -r '.explanation')
echo "$RISPOSTA" | grep -iE "$VIETATI"
# → PASS se nessun match (output vuoto)
```

Se il LLM produce termini tecnici → aggiustare il prompt di sistema prima di dichiarare DONE.

---

## Definition of Done

```bash
# 1. /explain restituisce testo dinamico (non identico ai template M0)
curl -s http://localhost:8000/explain | jq '.explanation'
# → PASS se stringa non vuota

# 2. Fallback funziona con EXPLAINER_USE_LLM=false
# Impostare in .env EXPLAINER_USE_LLM=false
curl -s http://localhost:8000/explain | jq '.explanation'
# → PASS se restituisce template statico M0 senza errori

# 3. Invariante M0-05: nessun termine tecnico nella risposta
# (vedi sezione "Verifica invariante" sopra)
# → PASS se grep -iE restituisce output vuoto

# 4. Test suite PASS con mock LLM
php artisan test --stop-on-failure
# → PASS (≥ 19 test, 0 failure)

# 5. Linter
vendor/bin/pint --test
# → PASS {"result":"pass"}

# 6. HANDOFF prodotto
ls coord/HANDOFF_spiegami_dynamic_m2.md
# → PASS se esiste con correlation_id
```

---

## Template HANDOFF da produrre

Creare `coord/HANDOFF_spiegami_dynamic_m2.md` con:
```
status: DONE
correlation_id: <uuid-v4>   ← OBBLIGATORIO
branch: task/spiegami_dynamic_m2
```
