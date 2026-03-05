# TASK_02_chat_ai_m2

## Metadata
- **created**: 2026-03-02T00:00:00Z
- **updated**: 2026-03-02T00:00:00Z
- **assignee**: Claude
- **status**: BLOCKED
- **milestone**: M2
- **risk tier**: MED

---

## Obiettivo
Aggiungere una chat in italiano powered by Claude API (modello `claude-haiku-4-5`). L'utente digita una domanda sulla coda fatture → il sistema invia contesto snapshot (stato, pending, errors) + domanda → Claude risponde in italiano plain-language. La chat è stateless (nessuna storia tra richieste).

> **Costo stimato**: ~€0,001 per chiamata. Budget suggerito MVP: max 100 chiamate/giorno.

---

## Scope
- [ ] Aggiungere `ANTHROPIC_API_KEY=` in `.env.example`
- [ ] Creare `config/ai.php` con `model` (`claude-haiku-4-5`), `max_tokens` (512), `temperature` (0.3)
- [ ] Creare `app/Services/ChatService.php`: prepara system prompt con snapshot coda (stato, pending, errors), chiama API Claude via SDK Anthropic PHP, restituisce stringa risposta in italiano
- [ ] Creare `app/Http/Controllers/ChatController.php`: `POST /chat` valida input (max 500 char, non vuoto) → `ChatService::ask()` → JSON `{"reply":"..."}`
- [ ] Aggiungere route `POST /chat` in `routes/web.php` (con protezione CSRF via `@csrf` nel form)
- [ ] Aggiungere widget chat in `resources/views/dashboard.blade.php`: campo testo + pulsante "Chiedi" + area risposta (JS fetch, no page reload)
- [ ] Gestione errore API (timeout, chiave non valida, rate limit) → HTTP 503 JSON + log strutturato AppException
- [ ] Test: mock `ChatService` nei test Feature per non consumare API reale
- [ ] Pint PASS, test PASS, HANDOFF prodotto

## Non-scope
- NON memorizzare la storia della chat (stateless)
- NON implementare autenticazione o middleware aggiuntivi
- NON implementare rate limiting (post-M2)
- NON usare streaming response
- NON aggiungere dipendenza `anthropic/sdk` se già presente — verificare `composer.json` prima

---

## Allowed paths
```
app/Services/ChatService.php              ← nuovo file
app/Http/Controllers/ChatController.php  ← nuovo file
config/ai.php                             ← nuovo file
resources/views/dashboard.blade.php
routes/web.php
.env.example
tests/Feature/ChatTest.php               ← nuovo file test smoke
```

## Forbidden paths
```
app/Services/TrafficLightService.php
app/Services/ExplainerService.php
app/Services/LogReaderService.php
database/
bootstrap/app.php
Dockerfile
docker-compose.yml
coord/
```

---

## Dipendenze
- **BLOCKED_BY**: TASK_01_log_viewer_m2
- **BLOCKS**: TASK_03_spiegami_dynamic_m2
- **PARALLEL con**: nessuno

---

## ⚠️ Pre-check obbligatorio

Prima di iniziare:
1. Verificare `ANTHROPIC_API_KEY` disponibile in `.env` locale
2. Verificare `anthropic/anthropic-sdk-php` (o equivalente) installabile: `composer require anthropic-ai/sdk`
3. Se chiave non disponibile → segnalare nel HANDOFF come BLOCKED con motivo "API key assente"

---

## Definition of Done

```bash
# 1. POST /chat risponde JSON con risposta in italiano
curl -s -X POST http://localhost:8000/chat \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $(php artisan tinker --execute='echo csrf_token();')" \
  -d '{"question":"Perché ci sono fatture in errore?"}'
# → PASS se {"reply":"..."} con testo italiano

# 2. Gestione errore API: con ANTHROPIC_API_KEY invalida
# → PASS se HTTP 503 {"error":"Servizio temporaneamente non disponibile"}

# 3. Test suite no regressioni (ChatService mockato)
php artisan test --stop-on-failure
# → PASS (≥ 19 test, 0 failure)

# 4. Linter
vendor/bin/pint --test
# → PASS {"result":"pass"}

# 5. HANDOFF prodotto
ls coord/HANDOFF_chat_ai_m2.md
# → PASS se esiste con correlation_id
```

---

## System prompt suggerito (base)

```
Sei un assistente per la gestione della fatturazione elettronica di una PMI italiana.
Rispondi SEMPRE in italiano, in modo semplice e diretto, senza termini tecnici.
Contesto attuale della coda SDI:
- Stato semaforo: {stato}
- Fatture in attesa: {pending}
- Fatture in errore: {errors}
Rispondi in massimo 3 frasi.
```

---

## Template HANDOFF da produrre

Creare `coord/HANDOFF_chat_ai_m2.md` con:
```
status: DONE
correlation_id: <uuid-v4>   ← OBBLIGATORIO
branch: task/chat_ai_m2
```
