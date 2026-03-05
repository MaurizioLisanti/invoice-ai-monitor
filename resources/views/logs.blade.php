<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log — invoice-ai-monitor</title>
    <style>
        /* ── Reset & base ──────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #1e293b; min-height: 100vh; }

        /* ── Layout ────────────────────────────────────────────── */
        .container { max-width: 960px; margin: 0 auto; padding: 2rem 1rem; }
        header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
        h1 { font-size: 1.4rem; font-weight: 700; letter-spacing: -.02em; }

        /* ── Card ──────────────────────────────────────────────── */
        .card { background: white; border-radius: 1rem; padding: 1.5rem;
                box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 1.5rem; }

        /* ── Nav ───────────────────────────────────────────────── */
        .back-link {
            font-size: .875rem; color: #64748b; text-decoration: none;
            border: 1px solid #e2e8f0; border-radius: .5rem; padding: .35rem .75rem;
        }
        .back-link:hover { background: #f1f5f9; }
        .footer-info { font-size: .75rem; color: #94a3b8; }

        /* ── Tabella ───────────────────────────────────────────── */
        .table-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .table-header h3 { font-size: 1rem; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        thead th { background: #f1f5f9; text-align: left; padding: .65rem 1rem; font-weight: 600; color: #475569; }
        tbody td { padding: .6rem 1rem; border-top: 1px solid #f1f5f9; vertical-align: top; }
        tbody tr:hover td { background: #f8fafc; }

        .text-muted { color: #94a3b8; font-style: italic; }

        /* ── Badge livello ─────────────────────────────────────── */
        .level {
            display: inline-block; padding: .2rem .65rem; border-radius: 999px;
            font-size: .72rem; font-weight: 700; letter-spacing: .04em; white-space: nowrap;
        }
        .level-CRITICAL,
        .level-EMERGENCY { background: #ede9fe; color: #6d28d9; }
        .level-ERROR     { background: #fee2e2; color: #991b1b; }
        .level-WARNING   { background: #fef3c7; color: #92400e; }
        .level-INFO      { background: #dbeafe; color: #1e40af; }
        .level-DEBUG     { background: #f1f5f9; color: #475569; }
        .level-UNKNOWN   { background: #f1f5f9; color: #94a3b8; }

        /* ── Celle ─────────────────────────────────────────────── */
        .msg-cell { font-family: ui-monospace, 'Cascadia Code', monospace; font-size: .8rem; }
        .context-snippet {
            margin-top: .3rem; font-size: .72rem; color: #94a3b8;
            font-family: ui-monospace, 'Cascadia Code', monospace;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 520px;
        }
        .datetime-cell { font-size: .78rem; color: #64748b; white-space: nowrap; }
    </style>
</head>
<body>
<div class="container">

    <header>
        <h1>invoice-ai-monitor</h1>
        <a class="back-link" href="{{ route('dashboard') }}">← Dashboard</a>
    </header>

    <div class="card">
        <div class="table-header">
            <h3>Log recenti</h3>
            <span class="footer-info">{{ count($entries) }} voci</span>
        </div>

        @if(count($entries) === 0)
            <p class="text-muted" style="text-align:center;padding:2rem 1rem">
                Nessun log disponibile.
            </p>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width:110px">Livello</th>
                        <th>Messaggio</th>
                        <th style="width:160px">Data / ora</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        <tr>
                            <td>
                                <span class="level level-{{ $entry['level_name'] }}">
                                    {{ $entry['level_name'] }}
                                </span>
                            </td>
                            <td class="msg-cell">
                                {{ $entry['message'] }}
                                @if(! empty($entry['context']))
                                    <div class="context-snippet" title="{{ json_encode($entry['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
                                        {{ json_encode($entry['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                                    </div>
                                @endif
                            </td>
                            <td class="datetime-cell">
                                {{ $entry['datetime'] ? substr($entry['datetime'], 0, 19) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>
</body>
</html>
