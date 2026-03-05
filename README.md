# 🟢 Invoice AI Monitor
### AI-powered monitoring platform with agentic pipeline
![Tests](https://img.shields.io/badge/Tests-49%20PASS-brightgreen)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20)
![AI](https://img.shields.io/badge/AI-OpenRouter-412991)
> Piattaforma di monitoraggio universale AI-powered.  
> Oggi monitora flussi di fatturazione SDI — domani qualsiasi flusso dati aziendale.  
> Costruita con una pipeline agentiva a 7 agenti specializzati.

---

## ✨ Funzionalità principali

- 🚦 **Semaforo intelligente** — Verde / Giallo / Rosso basato su soglie reali
- 🧠 **Analisi AI** — Stato / Diagnosi / Azione generati automaticamente
- 💬 **Chat AI integrata** — Conversazione libera con OpenRouter (futuro RAG)
- 📊 **Log viewer** — Storico eventi con ricerca e filtri
- ⏱️ **Scheduler automatico** — Job ogni minuto, sempre aggiornato
- 🎮 **Simulatore realistico** — Scenari green / yellow / red con dati italiani reali
- ✅ **49 test automatici** — Zero regressioni, qualità verificabile

---


---

## 🏗️ Architettura

```
Browser
   │
   ▼
Dashboard (Blade + JS polling 60s)
   │
   ├── GET  /          → semaforo + tabella eventi
   ├── GET  /status    → JSON { status, pending, errors }
   ├── GET  /explain   → { stato, diagnosi, azione } via ExplainerService
   ├── GET  /logs      → log viewer
   └── POST /chat      → ChatService → OpenRouter API
          │
          ├── TrafficLightService   → logica semaforo
          ├── ExplainerService      → analisi AI + fallback template
          ├── ChatService           → chat OpenRouter
          └── LogReaderService      → lettura log JSON
               │
               └── MySQL → invoices_queue
```

---

## 🤖 Pipeline Agentiva — Il metodo

> *"Il developer del futuro non scrive codice riga per riga — guida l'AI, valida il risultato, migliora il metodo."*

Questo progetto è stato costruito usando una **pipeline agentiva strutturata** con 7 agenti specializzati:

| # | Agente | Ruolo |
|---|--------|-------|
| 01 | **Discovery** | Analizza il problema, produce la SPEC |
| 02 | **Repo Seed** | Scaffolding iniziale del progetto |
| 03 | **Planner** | Pianifica i task per wave |
| 04 | **Executor** | Scrive il codice, un task alla volta |
| 05 | **Reviewer** | Code review formale P0 / P1 / P2 |
| 06 | **Complexity Manager** | Gestisce wave e dipendenze |
| 07 | **Integration Guard** | Verifica WAVE_PASSED o WAVE_FAILED |

### Wave completate

```
Wave M0 — Fondamenta      → scaffold, DB, semaforo, dashboard
Wave M1 — Qualità         → guardrail, test, documentazione
Wave M2 — Feature Complete → log viewer, chat AI, spiegami, scheduler
Wave M3 — Demo Ready      → simulatore realistico, OpenRouter, fix AI
```

### Garanzie di qualità per ogni task

```
✅ Test PASS     — zero fallimenti tollerati
✅ Pint PASS     — code style Laravel standard
✅ Reviewer      — verdict P0/P1/P2 formale
✅ Integration Guard — WAVE_PASSED prima di procedere
```

---

## 🛠️ Stack tecnologico

| Layer | Tecnologia |
|-------|-----------|
| Backend | PHP 8.4 + Laravel 11 |
| Database | MySQL (produzione) + SQLite (test) |
| AI | OpenRouter API (modello free) |
| Frontend | Blade + CSS inline + JS vanilla |
| Test | PHPUnit + RefreshDatabase |
| Code style | Laravel Pint |
| Scheduler | Laravel Task Scheduling |

---

## 🚀 Quick Start

### Prerequisiti
```bash
# XAMPP con MySQL attivo
# PHP 8.4
# Composer
```

### Installazione
```bash
git clone https://github.com/MaurizioLisanti/invoice-ai-monitor.git
cd invoice-ai-monitor
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### Avvio
```bash
# Terminale 1 — server web
php -S 0.0.0.0:8080 -t public

# Terminale 2 — simulatore (opzionale)
php artisan invoice:simulate --scenario=red

# Browser
http://localhost:8080
```

### Scenari simulatore
```bash
php artisan invoice:simulate --scenario=green   # Semaforo verde
php artisan invoice:simulate --scenario=yellow  # Semaforo giallo
php artisan invoice:simulate --scenario=red     # Semaforo rosso
```

### Test
```bash
php artisan test
# → 49/49 PASS
```

---

## 🔮 Visione futura

La piattaforma è progettata per essere **universale**:

- 📦 **Oggi** — monitoraggio flusso fatture SDI
- 🏭 **Domani** — monitoraggio impianti industriali
- 📦 **Poi** — qualsiasi flusso dati aziendale
- 🤖 **Futuro** — RAG avanzato + memoria conversazionale

L'architettura modulare con Services separati permette di sostituire la sorgente dati senza toccare il layer AI e UI.

---

## 📁 Struttura progetto

```
invoice-ai-monitor/
├── app/
│   ├── Console/Commands/
│   │   ├── CheckInvoiceQueueCommand.php  ← scheduler
│   │   └── SimulateInvoicesCommand.php   ← simulatore
│   ├── Http/Controllers/
│   │   └── DashboardController.php
│   └── Services/
│       ├── TrafficLightService.php       ← logica semaforo
│       ├── ExplainerService.php          ← analisi AI
│       ├── ChatService.php               ← chat OpenRouter
│       └── LogReaderService.php          ← log viewer
├── resources/views/
│   ├── dashboard.blade.php
│   └── logs.blade.php
├── tests/
│   ├── Feature/                          ← 40+ test
│   └── Unit/                             ← 9+ test
└── pipeline-agentiva/                    ← prompt pipeline
    ├── PROMPT_01_discovery.md
    ├── PROMPT_02_repo_seed.md
    ├── PROMPT_03_planner.md
    ├── PROMPT_04_executor.md
    ├── PROMPT_05_reviewer.md
    ├── PROMPT_06_complexity_manager.md
    └── PROMPT_07_integration_guard.md
```

---

## 👤 Autore

**MAURIZIO LISANTI**  
Freelance Backend Developer | PHP & Python | AI & SaaS  
Shopify Partner | Founder PropLUG | Codemotion Partner

- 💼 LinkedIn: https://www.linkedin.com/in/maurizio-lisanti/
- 📧 Email: lisa.mau@libero.it
- 🌐 PropLUG: coming soon

---

## 📄 Licenza

MIT License — libero utilizzo con attribuzione.

---

*"AI-powered agentic development pipeline in produzione"*
