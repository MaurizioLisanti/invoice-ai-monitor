<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>invoice-ai-monitor</title>
    <style>
        /* ── Reset & base ──────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #1e293b; min-height: 100vh; }

        /* ── Layout ────────────────────────────────────────────── */
        .container { max-width: 960px; margin: 0 auto; padding: 2rem 1rem; }
        header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
        h1 { font-size: 1.4rem; font-weight: 700; letter-spacing: -.02em; }

        /* ── Card base ─────────────────────────────────────────── */
        .card { background: white; border-radius: 1rem; padding: 1.5rem;
                box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 1.5rem; }

        /* ── Semaforo ───────────────────────────────────────────── */
        .semaforo-wrap { display: flex; align-items: center; gap: 2rem; }
        .semaforo { display: flex; flex-direction: column; gap: 8px;
                    background: #1e293b; border-radius: 14px; padding: 14px; flex-shrink: 0; }
        .light { width: 40px; height: 40px; border-radius: 50%; opacity: .18; transition: opacity .35s ease; }
        .light.active { opacity: 1; box-shadow: 0 0 14px currentColor; }
        .light-red    { background: #ef4444; color: #ef4444; }
        .light-yellow { background: #f59e0b; color: #f59e0b; }
        .light-green  { background: #22c55e; color: #22c55e; }

        .stato-info h2 { font-size: 1.5rem; font-weight: 700; margin-bottom: .75rem; }
        .stato-verde  { color: #16a34a; }
        .stato-giallo { color: #d97706; }
        .stato-rosso  { color: #dc2626; }

        .counters { display: flex; gap: 2rem; }
        .counter { text-align: center; }
        .counter-val { font-size: 2.5rem; font-weight: 800; line-height: 1; }
        .counter-lbl { font-size: .75rem; color: #64748b; margin-top: .25rem; text-transform: uppercase; letter-spacing: .05em; }
        .counter-warn .counter-val { color: #dc2626; }

        /* ── Spiegami ───────────────────────────────────────────── */
        #btn-spiegami {
            background: #3b82f6; color: white; border: none; border-radius: .625rem;
            padding: .65rem 1.5rem; font-size: .95rem; font-weight: 600;
            cursor: pointer; transition: background .2s;
        }
        #btn-spiegami:hover { background: #2563eb; }
        #btn-spiegami:disabled { background: #93c5fd; cursor: wait; }

        #spiegami-box {
            display: none; margin-top: 1rem;
            background: #eff6ff; border-radius: .75rem; padding: 1.25rem;
            border-left: 4px solid #3b82f6; color: #1e3a5f;
        }
        #spiegami-box.warn  { background: #fff7ed; border-color: #f59e0b; color: #78350f; }
        #spiegami-box.error { background: #fef2f2; border-color: #ef4444; color: #7f1d1d; }

        .spiegami-section { margin-bottom: .875rem; }
        .spiegami-section:last-child { margin-bottom: 0; }
        .spiegami-label {
            font-size: .68rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .08em; opacity: .6; margin-bottom: .2rem;
        }
        .spiegami-value { line-height: 1.65; }

        /* ── Tabella eventi ─────────────────────────────────────── */
        .table-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .table-header h3 { font-size: 1rem; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        thead th { background: #f1f5f9; text-align: left; padding: .65rem 1rem; font-weight: 600; color: #475569; }
        tbody td { padding: .6rem 1rem; border-top: 1px solid #f1f5f9; }
        tbody tr:hover td { background: #f8fafc; }

        .pill { display: inline-block; padding: .2rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 600; }
        .pill-pending  { background: #fef9c3; color: #854d0e; }
        .pill-error,
        .pill-rejected { background: #fee2e2; color: #991b1b; }
        .pill-sent,
        .pill-accepted { background: #dcfce7; color: #166534; }
        .pill-unknown  { background: #f1f5f9; color: #475569; }

        .text-muted { color: #94a3b8; font-style: italic; }

        /* ── Footer ─────────────────────────────────────────────── */
        .footer-info { font-size: .75rem; color: #94a3b8; text-align: right; margin-top: 1rem; }

        /* ── Chat AI ─────────────────────────────────────────────── */
        #chat-input {
            width: 100%; padding: .65rem .875rem; font-size: .95rem;
            border: 1px solid #e2e8f0; border-radius: .625rem;
            font-family: inherit; resize: vertical; min-height: 3.5rem;
            margin-bottom: .75rem; display: block;
        }
        #chat-input:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,.15); }
        #btn-chat {
            background: #0d9488; color: white; border: none; border-radius: .625rem;
            padding: .65rem 1.5rem; font-size: .95rem; font-weight: 600;
            cursor: pointer; transition: background .2s;
        }
        #btn-chat:hover { background: #0f766e; }
        #btn-chat:disabled { background: #5eead4; cursor: wait; }
        #chat-box {
            display: none; margin-top: 1rem;
            background: #f0fdfa; border-radius: .75rem; padding: 1.25rem;
            border-left: 4px solid #0d9488; line-height: 1.7; color: #134e4a;
            white-space: pre-wrap;
        }
        #chat-box.error { background: #fef2f2; border-color: #ef4444; color: #7f1d1d; }
    </style>
</head>
<body>
<div class="container">

    <header>
        <h1>invoice-ai-monitor</h1>
        <nav style="display:flex;align-items:center;gap:1.5rem">
            <a href="{{ route('logs') }}" style="font-size:.875rem;color:#64748b;text-decoration:none;border:1px solid #e2e8f0;border-radius:.5rem;padding:.35rem .75rem">Log</a>
            <span class="footer-info">Aggiornamento automatico ogni {{ $refreshSeconds }}s</span>
        </nav>
    </header>

    {{-- ── Semaforo ──────────────────────────────────────────── --}}
    <div class="card">
        <div class="semaforo-wrap">
            <div class="semaforo" id="semaforo">
                <div class="light light-red    {{ $snapshot['status'] === 'red'    ? 'active' : '' }}"></div>
                <div class="light light-yellow {{ $snapshot['status'] === 'yellow' ? 'active' : '' }}"></div>
                <div class="light light-green  {{ $snapshot['status'] === 'green'  ? 'active' : '' }}"></div>
            </div>

            <div class="stato-info">
                <h2 id="stato-label" class="stato-{{ $snapshot['status'] === 'red' ? 'rosso' : ($snapshot['status'] === 'yellow' ? 'giallo' : 'verde') }}">
                    @if($snapshot['status'] === 'green')  Tutto OK
                    @elseif($snapshot['status'] === 'yellow') Attenzione
                    @else Problema Critico
                    @endif
                </h2>

                <div class="counters">
                    <div class="counter {{ $snapshot['pending'] > config('invoice.semaforo_red_pending') ? 'counter-warn' : '' }}">
                        <div class="counter-val" id="cnt-pending">{{ $snapshot['pending'] }}</div>
                        <div class="counter-lbl">In attesa</div>
                    </div>
                    <div class="counter {{ $snapshot['errors'] > config('invoice.semaforo_red_errors') ? 'counter-warn' : '' }}">
                        <div class="counter-val" id="cnt-errors">{{ $snapshot['errors'] }}</div>
                        <div class="counter-lbl">In errore</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Spiegami ──────────────────────────────────────────── --}}
    <div class="card">
        <button id="btn-spiegami" onclick="spiegami()">Spiegami cosa succede</button>
        <div id="spiegami-box"></div>
    </div>

    {{-- ── Chat AI ───────────────────────────────────────────── --}}
    <div class="card">
        <h3 style="font-size:1rem;font-weight:600;margin-bottom:.75rem">Chiedi all'AI</h3>
        <textarea id="chat-input" placeholder="Es: Perché ci sono così tante fatture in errore?"></textarea>
        <button id="btn-chat" onclick="sendChat()">Chiedi all'AI</button>
        <div id="chat-box"></div>
    </div>

    {{-- ── Tabella eventi ────────────────────────────────────── --}}
    <div class="card">
        <div class="table-header">
            <h3>Ultimi eventi</h3>
            <span class="footer-info">Aggiornato: <span id="last-updated">{{ $snapshot['last_updated'] }}</span></span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Riferimento fattura</th>
                    <th>Stato</th>
                    <th>Messaggio errore</th>
                    <th>Aggiornato il</th>
                </tr>
            </thead>
            <tbody id="events-tbody">
                @forelse($events as $event)
                    <tr>
                        <td>{{ $event->invoice_ref ?? '—' }}</td>
                        <td>
                            <span class="pill pill-{{ in_array($event->status, ['error','rejected','pending','sent','accepted']) ? $event->status : 'unknown' }}">
                                {{ $event->status }}
                            </span>
                        </td>
                        <td class="{{ $event->error_message ? '' : 'text-muted' }}">
                            {{ $event->error_message ?? 'Nessun errore' }}
                        </td>
                        <td>{{ $event->updated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted" style="text-align:center;padding:1.5rem">Nessun evento disponibile.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<script>
const REFRESH_MS = {{ $refreshSeconds * 1000 }};

const STATUS_LABELS = {
    green:  'Tutto OK',
    yellow: 'Attenzione',
    red:    'Problema Critico'
};
const STATUS_CSS = {
    green:  'stato-verde',
    yellow: 'stato-giallo',
    red:    'stato-rosso'
};

async function fetchStatus() {
    try {
        const res  = await fetch('{{ route('dashboard.status') }}');
        if (!res.ok) return;
        const data = await res.json();
        updateSemaforo(data);
    } catch (e) {
        console.warn('[invoice-ai-monitor] polling error', e);
    }
}

function updateSemaforo(data) {
    // Luci
    document.querySelectorAll('.light').forEach(l => l.classList.remove('active'));
    const activeLight = document.querySelector(`.light-${data.status}`);
    if (activeLight) activeLight.classList.add('active');

    // Contatori
    document.getElementById('cnt-pending').textContent = data.pending;
    document.getElementById('cnt-errors').textContent  = data.errors;

    // Etichetta stato
    const label = document.getElementById('stato-label');
    label.textContent = STATUS_LABELS[data.status] || data.status;
    label.className   = STATUS_CSS[data.status] || '';

    // Timestamp
    document.getElementById('last-updated').textContent = data.last_updated;
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

async function spiegami() {
    const btn = document.getElementById('btn-spiegami');
    const box = document.getElementById('spiegami-box');

    btn.disabled      = true;
    btn.textContent   = 'Analisi in corso...';
    box.style.display = 'block';
    box.className     = '';
    box.innerHTML     = '<span style="opacity:.5">...</span>';

    try {
        const res  = await fetch('{{ route('dashboard.explain') }}');
        const data = await res.json();

        box.className = data.status === 'red' ? 'error' : (data.status === 'yellow' ? 'warn' : '');
        box.innerHTML = `
            <div class="spiegami-section">
                <div class="spiegami-label">Stato</div>
                <div class="spiegami-value">${escHtml(data.stato)}</div>
            </div>
            <div class="spiegami-section">
                <div class="spiegami-label">Diagnosi probabile</div>
                <div class="spiegami-value">${escHtml(data.diagnosi)}</div>
            </div>
            <div class="spiegami-section">
                <div class="spiegami-label">Azione consigliata</div>
                <div class="spiegami-value">${escHtml(data.azione)}</div>
            </div>`;
    } catch (e) {
        box.innerHTML = 'Errore nel caricamento della spiegazione. Riprova tra qualche secondo.';
        box.className = 'error';
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Spiegami cosa succede';
    }
}

async function sendChat() {
    const btn   = document.getElementById('btn-chat');
    const input = document.getElementById('chat-input');
    const box   = document.getElementById('chat-box');
    const token = document.querySelector('meta[name="csrf-token"]').content;

    const question = input.value.trim();
    if (! question) { input.focus(); return; }

    btn.disabled    = true;
    btn.textContent = 'Analisi in corso...';
    box.style.display = 'block';
    box.className   = '';
    box.textContent = '...';

    try {
        const res  = await fetch('{{ route('chat.ask') }}', {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept':       'application/json',
            },
            body: JSON.stringify({ question }),
        });
        const data = await res.json();

        if (! res.ok) {
            box.textContent = data.error || 'Errore nel servizio AI. Riprova tra qualche secondo.';
            box.className   = 'error';
        } else {
            box.textContent = data.reply;
            box.className   = '';
        }
    } catch (e) {
        box.textContent = 'Errore di rete. Riprova tra qualche secondo.';
        box.className   = 'error';
    } finally {
        btn.disabled    = false;
        btn.textContent = "Chiedi all'AI";
    }
}

// Avvia polling
setInterval(fetchStatus, REFRESH_MS);
</script>
</body>
</html>
