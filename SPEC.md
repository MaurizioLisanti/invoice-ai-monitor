# Project Description — invoice-ai-monitor
> Generato da PROMPT_01_discovery_interview_v3 · 2026-02-23

---

**Project name**: `invoice-ai-monitor`
**One-liner**: Dashboard per responsabile amministrativo che mostra in tempo reale lo stato della coda SDI così da bloccare ritardi di fatturazione prima che diventino problemi.
**Value proposition**: Le PMI italiane perdono tempo (e credibilità) scoprendo in ritardo che le fatture elettroniche sono bloccate in coda o in errore. `invoice-ai-monitor` offre un semaforo visuale immediato + spiegazioni in italiano plain-language, eliminando la dipendenza dallo sviluppatore per la diagnosi quotidiana. Vantaggio competitivo: zero integrazioni esterne richieste — funziona sopra la tabella MySQL che il sistema di fatturazione già produce.

---

### Business model
- **Deployment**: On-premise / VPS aziendale (single-tenant)
- **Monetizzazione**: Nessuna (strumento interno)
- **Segmento di mercato**: PMI italiana con obbligo di fatturazione elettronica SDI

---

### Target users
- **Primary**: Responsabile amministrativo — ogni mattina + on-alert — livello non-tecnico
- **Secondary**: Sviluppatore interno — ad hoc (troubleshooting) — livello dev

---

### Primary workflow (max 5 step)
1. **Trigger**: Scheduler Laravel esegue ogni 60 secondi una query sulla tabella MySQL custom della coda fatture.
2. **Calcolo**: Il sistema conta `pending` e `errori` e applica le soglie semaforo → produce stato (`green` / `yellow` / `red`) + snapshot metriche.
3. **Render dashboard**: La pagina (Livewire o polling AJAX) mostra semaforo aggiornato + tabella eventi (ultime N fatture con stato, timestamp, messaggio errore).
4. **Alert passivo**: Se lo stato è `yellow` o `red`, la dashboard evidenzia il contatore critico con colore e badge.
5. **Spiegami**: L'utente clicca il pulsante → il sistema restituisce una spiegazione in italiano plain-language generata da template predefiniti in base allo stato e ai contatori rilevati.

---

### Inputs
- **Sources**: Tabella MySQL custom già popolata dal sistema di fatturazione esistente
- **Formats**: Righe SQL con campi di stato, timestamp, contatori errori
- **Example** `[ASSUNTO — A5]`:
  ```sql
  -- Schema minimo atteso (da verificare con il team)
  invoices_queue (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_ref   VARCHAR(64),
    status        ENUM('pending','sent','accepted','rejected','error'),
    error_message TEXT NULL,
    created_at    TIMESTAMP,
    updated_at    TIMESTAMP
  )
  ```

---

### Outputs
- **Destinations**: Browser (dashboard web interna)
- **Formats**: HTML/Blade — semaforo SVG/CSS, tabella HTML, testo italiano plain-language
- **Example**:
  > Stato ROSSO — Ci sono 12 fatture in errore e 53 in attesa di invio. Il sistema di fatturazione potrebbe essere bloccato. Contatta il supporto tecnico o verifica la connessione al nodo SDI.

---

### Tech constraints
- **Stack**: PHP 8.4 · Laravel 11 · MySQL (XAMPP/MariaDB o MySQL Server locale) — CONFERMATO
- **Runtime / Dev**: Laravel Herd (Windows) — serve i siti da `~/Herd/` al TLD `.test`; in alternativa `php artisan serve` su `localhost:8000`
- **Runtime / Deploy**: VPS o server aziendale (deploy diretto o zip upload; Docker opzionale ma non richiesto)
- **Database / Storage**: MySQL/MariaDB locale su `127.0.0.1:3306` — tabella custom già esistente; nessun DB aggiuntivo
- **External integrations**: Nessuna nel MVP
- **Offline / network limits**: Funziona in rete locale aziendale; nessun requisito cloud
- **Note Docker**: File `Dockerfile` e `docker-compose.yml` mantenuti nel repo per deploy futuro su VPS, ma NON utilizzati in sviluppo locale

---

### Security & data sensitivity
- **PII**: Nessun dato personale sensibile trattato dalla dashboard (solo contatori e stati tecnici) `[DA VERIFICARE — verificare se invoice_ref o campi aggiuntivi contengono dati fiscali identificativi]`
- **Confidentiality level**: Interno
- **Compliance**: Nessun requisito GDPR esplicito nell'MVP `[DA VERIFICARE se invoice_ref mappa a dati anagrafici]`
- **Autenticazione**: Assente nell'MVP — accesso protetto solo dalla rete aziendale `[ASSUNTO — A2]`

---

### Observability
- **Log format**: JSON strutturato — SÌ (obbligatorio per governance futura)
- **Log consumer**: Umano (sviluppatore in troubleshooting) + sistema (futuro)
- **Log retention**: 30 giorni `[ASSUNTO — A4]`
- **Audit trail**: NO — non richiesto per tool interno senza autenticazione
- **Metrics chiave**: latenza p95 della pagina dashboard, error rate query MySQL, stato semaforo nel tempo (storico)
- **correlation_id**: NO — sistema single-agent, nessuna governance multi-agente

---

### Performance / cost budgets
- **Latency p95**: < 2s (caricamento completo dashboard inclusa query MySQL)
- **Volume atteso**: Max 10 utenti simultanei, uso locale
- **Timeout / cost constraints**: Query MySQL timeout 5s; nessun costo API esterno
- **Costo per chiamata stimato**: €0,00 — nessuna API esterna `[DA VERIFICARE se si aggiunge LLM per Spiegami in futuro]`

---

### Non-goals (explicit)
- NON farà: autenticazione / gestione utenti / ruoli
- NON farà: multi-tenant o white-label per più aziende
- NON farà: integrazione diretta con SDI o intermediari (Aruba, Zucchetti, ecc.)
- NON farà: app mobile o PWA
- NON farà: gestione pagamenti o funzionalità di invio fatture

---

### MVP Acceptance Criteria (M0 demoable)
Ogni criterio è PASS/FAIL — verificabile con demo o script in ≤ 30 minuti.

> **M0 COMPLETATA** — 2026-02-28 · Wave M0 WAVE_PASSED · tutti i criteri PASS (Integration Guard v1 + Reviewer v2)

- [x] **Semaforo VERDE**: con 0 errori e pending ≤ 10 la dashboard mostra verde — PASS se il colore è verde e i contatori corrispondono
- [x] **Semaforo GIALLO**: con pending = 15 o errori = 5 la dashboard mostra giallo — PASS se il colore è giallo
- [x] **Semaforo ROSSO**: con pending = 55 o errori = 12 la dashboard mostra rosso — PASS se il colore è rosso
- [x] **Tabella eventi**: la tabella mostra gli ultimi 20 record con colonne stato, data, messaggio errore — PASS se i dati corrispondono alla tabella MySQL
- [x] **Spiegami**: il click sul pulsante produce un testo in italiano leggibile e contestuale allo stato attivo — PASS se il testo non contiene termini tecnici non spiegati
- [x] **Performance**: la pagina si carica in < 2s misurata con browser DevTools su rete locale — PASS se p95 < 2s su 10 ricaricamenti consecutivi
- [x] **Herd / artisan serve**: `make serve` (o Herd che serve il sito) porta la dashboard raggiungibile su `http://localhost:8000` (o `http://invoice-ai-monitor.test`) senza errori — PASS se la pagina risponde HTTP 200

---

### Milestone M1 Acceptance Criteria
> **M1 COMPLETATA** — 2026-02-28 · Wave M1 WAVE_PASSED

Ogni criterio è PASS/FAIL.

- [x] **Error handling**: HTTP 503 su errore DB — PASS se `/status` restituisce `{"error":"Servizio temporaneamente non disponibile"}` con status 503 quando MySQL non è raggiungibile
- [x] **Log JSON strutturato**: output verificabile con `make logs` — PASS se ogni riga del log è JSON valido con campi `message`, `context`, `level_name`, `datetime`
- [x] **Test suite**: `make test` PASS su macchina pulita — PASS se 19/19 test superati (9 Feature M0 + 5 TrafficLight unit + 3 Explainer unit + 2 example)
- [x] **Runbook**: quick start funzionante da README — PASS se un utente nuovo riesce ad avviare la dashboard seguendo i 5 comandi di *Quick start*

---

### Risks / Unknowns
Scala: Probabilità (A=Alta / M=Media / B=Bassa) × Impatto (A/M/B)

- **R1** [P:A / I:A]: Lo schema della tabella MySQL custom è diverso da quello assunto → i campi `status` / `error_message` potrebbero avere nomi o tipi diversi → **Mitigazione**: mappare lo schema reale prima di scrivere una riga di codice; creare una migration di sola lettura (view) se il DB è condiviso
- **R2** [P:M / I:M]: Le spiegazioni in italiano template-based risultano troppo generiche per essere utili al responsabile → **Mitigazione**: test con utente reale (Mario) durante sviluppo; raccogliere feedback nelle prime 2 settimane
- **R3** [P:M / I:B]: Polling ogni 60s sotto carico (10 utenti) genera query ridondanti al MySQL → **Mitigazione**: cache Laravel (file/redis) con TTL 30s; query ottimizzata con indice su `status` + `updated_at`
- **R4** [P:B / I:M]: Assenza di autenticazione espone la dashboard a chiunque sulla rete aziendale → **Mitigazione**: documentare il rischio; aggiungere HTTP Basic Auth come quick-win post-MVP se richiesto

---

### Agent Routing Hints
- **Componenti sicurezza / auth** → Umano (non nell'MVP; se aggiunta post-MVP richiede revisione manuale)
- **Query MySQL, ottimizzazione indici, Docker Compose config** → Qwen Coder
- **Blade views, Livewire components, template spiegazioni italiano, test feature** → Claude
- **Note specifiche**: Il modulo "Spiegami" usa template PHP puri nell'MVP; se si decide di collegare un LLM (es. claude-haiku-4-5) per generazione dinamica, aggiungere sezione integrazioni e rivedere R4

---

### Assunzioni
- **[A1]** La tabella MySQL custom esiste già ed è già popolata dal sistema di fatturazione aziendale. La dashboard è read-only su questa tabella.
- **[A2]** Nessuna autenticazione nell'MVP: l'accesso è protetto solo dalla rete aziendale / VPN. Rischio accettato dal team.
- **[A3]** Le spiegazioni in italiano sono generate da template PHP predefiniti (switch su stato + soglie), senza chiamate a LLM esterni nell'MVP.
- **[A4]** Retention log: 30 giorni su file Laravel (`storage/logs`); nessun sistema di log aggregato esterno.
- **[A5]** Schema minimo della tabella: campi `status` (enum-like), `error_message` (text), `created_at`, `updated_at`. Da confermare prima dello sviluppo.
- **[A6]** Il refresh della dashboard avviene tramite polling HTTP ogni 60 secondi (configurabile via `.env`).

---

### HANDOFF CHECK
- [x] Stack dichiarato esplicitamente — PHP 8.4 · Laravel 11 · MySQL locale (XAMPP/MariaDB) · Laravel Herd
- [x] Primary workflow compilato — 5 step completi
- [x] M0 Acceptance Criteria presenti — 7 criteri, tutti PASS/FAIL
- [x] Non-goals compilati — 5 bullet
- [x] Rischi con scala P×I — 4 rischi
- [x] correlation_id dichiarato — NO
- [x] Nessun placeholder generico rimasto — tutti i [ASSUNTO] sono numerati e motivati
