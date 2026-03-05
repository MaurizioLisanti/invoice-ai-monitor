<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Simula il flusso reale della coda fatture SDI in locale.
 *
 * Gira in loop ogni 30 secondi, inserisce nuove fatture con nomi italiani
 * realistici, processa le pending più vecchie di 60 secondi (70% accepted,
 * 30% error con codici SDI reali). Supporta tre scenari via --scenario.
 * Si ferma con CTRL+C o al raggiungimento di --max-cycles (solo per test).
 */
class SimulateInvoicesCommand extends Command
{
    protected $signature = 'invoice:simulate
                            {--scenario=green : Scenario semaforo da simulare (green/yellow/red)}
                            {--max-cycles=0   : Numero massimo di cicli — 0 = infinito (uso interno per test)}';

    protected $description = 'Simula il flusso SDI: inserisce fatture e processa pending ogni 30s (CTRL+C per fermare)';

    private const  LOOP_SECONDS = 30;

    private const  ACCEPTED_RATE = 0.70;

    /** Codici errore reali SDI / FatturaPA */
    private const array SDI_ERRORS = [
        '00001' => 'File vuoto o non leggibile.',
        '00002' => 'File non integro — la firma digitale non è valida.',
        '00101' => 'Il formato della fattura non è conforme alle specifiche FatturaPA.',
        '00102' => 'Il file non corrisponde a nessuno degli schemi XML previsti.',
        '00103' => 'Identificativo SDI duplicato — fattura già ricevuta.',
        '00201' => 'Codice fiscale cedente/prestatore non trovato in Anagrafica Tributaria.',
        '00202' => 'Partita IVA del cedente/prestatore non trovata.',
        '00301' => 'Codice fiscale del cessionario/committente non trovato.',
        '00400' => 'Data della fattura successiva alla data di ricezione — anomalia temporale.',
        '00401' => 'Importo totale del documento incongruente con la somma delle righe.',
        '00411' => 'Numero fattura non univoco — già presente per lo stesso cedente.',
        '00413' => 'Regime fiscale non presente nella tabella di riferimento.',
    ];

    /** Cognomi italiani per invoice_ref realistici */
    private const array RAGIONI_SOCIALI = [
        'Rossi', 'Ferrari', 'Esposito', 'Bianchi', 'Romano', 'Colombo', 'Ricci', 'Marino',
        'Greco', 'Bruno', 'Gallo', 'Conti', 'De Luca', 'Mancini', 'Costa', 'Giordano',
        'Rizzo', 'Lombardi', 'Moretti', 'Barbieri', 'Fontana', 'Santoro', 'Marini', 'Rinaldi',
        'Caruso', 'Ferrara', 'Galli', 'Martini', 'Leone', 'Longo', 'Gentile', 'Martinelli',
        'Vitale', 'Lombardo', 'Serra', 'Coppola', 'De Angelis', 'Pellegrini', 'Ferretti',
        'Montanari', 'Valentini', 'Rossetti', 'Basile', 'Sartori', 'Fabbri', 'Villa',
        'Morelli', 'Bianco', 'Catalano', 'Messina',
    ];

    private bool $running = true;

    public function handle(): int
    {
        $scenario = (string) $this->option('scenario');
        $maxCycles = (int) $this->option('max-cycles');

        if (! in_array($scenario, ['green', 'yellow', 'red'], true)) {
            $this->error("Scenario non valido: '{$scenario}'. Usa green, yellow o red.");

            return self::FAILURE;
        }

        $this->registerSignalHandlers();

        $this->info("Simulatore avviato — scenario: <comment>{$scenario}</comment>");
        $this->info('Premi CTRL+C per fermare.');
        $this->newLine();

        $cycle = 0;

        while ($this->running) {
            $cycle++;

            $this->info('[Ciclo #'.$cycle.' — '.now()->format('H:i:s').']');

            $inserted = $this->insertInvoices($scenario);
            $this->line("  + {$inserted} fatture inserite");

            $processed = ['accepted' => 0, 'errors' => 0];

            if ($scenario !== 'red') {
                $processed = $this->processPending();
                $this->line("  \u{2713} {$processed['accepted']} accettate  \u{2717} {$processed['errors']} in errore");
            } else {
                $this->line('  — scenario RED: processing sospeso (accumulo)');
            }

            Log::info('simulator_cycle', [
                'cycle' => $cycle,
                'scenario' => $scenario,
                'inserted' => $inserted,
                'accepted' => $processed['accepted'],
                'errors' => $processed['errors'],
                'ts' => now()->toISOString(),
            ]);

            if ($maxCycles > 0 && $cycle >= $maxCycles) {
                break;
            }

            if (! $this->running) {
                break;
            }

            $this->line('  Prossimo ciclo tra '.self::LOOP_SECONDS.'s...');

            $this->sleepWithInterrupt(self::LOOP_SECONDS);
        }

        $this->newLine();
        $this->info('Simulatore fermato.');

        return self::SUCCESS;
    }

    // ── Logica inserimento ───────────────────────────────────────────

    /**
     * Inserisce 3-5 (green), 10-15 (yellow) o 25-35 (red) fatture pending.
     */
    private function insertInvoices(string $scenario): int
    {
        [$min, $max] = match ($scenario) {
            'yellow' => [10, 15],
            'red' => [25, 35],
            default => [3, 5],
        };

        $count = random_int($min, $max);
        $now = now();
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'invoice_ref' => $this->generateRef(),
                'status' => 'pending',
                'error_message' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('invoices_queue')->insert($rows);

        return $count;
    }

    /**
     * Processa tutte le fatture pending create più di 60 secondi fa.
     * 70% → accepted, 30% → error con codice SDI reale.
     *
     * @return array{accepted: int, errors: int}
     */
    private function processPending(): array
    {
        $cutoff = now()->subSeconds(60);

        $pending = DB::table('invoices_queue')
            ->where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->get(['id']);

        $accepted = 0;
        $errors = 0;

        foreach ($pending as $row) {
            if ((float) mt_rand() / (float) mt_getrandmax() < self::ACCEPTED_RATE) {
                DB::table('invoices_queue')
                    ->where('id', $row->id)
                    ->update(['status' => 'accepted', 'updated_at' => now()]);
                $accepted++;
            } else {
                [$code, $message] = $this->randomSdiError();
                DB::table('invoices_queue')
                    ->where('id', $row->id)
                    ->update([
                        'status' => 'error',
                        'error_message' => "Errore SDI {$code}: {$message}",
                        'updated_at' => now(),
                    ]);
                $errors++;
            }
        }

        return ['accepted' => $accepted, 'errors' => $errors];
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function generateRef(): string
    {
        $cognome = self::RAGIONI_SOCIALI[array_rand(self::RAGIONI_SOCIALI)];
        $normalized = strtoupper(str_replace([' ', "'"], ['_', ''], $cognome));
        $seq = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        return 'FT-'.now()->format('Y').'-'.$normalized.'-'.$seq;
    }

    /** @return array{0: string, 1: string} */
    private function randomSdiError(): array
    {
        $codes = array_keys(self::SDI_ERRORS);
        $code = $codes[array_rand($codes)];

        return [$code, self::SDI_ERRORS[$code]];
    }

    /**
     * Sleep a chunk di 1s per intercettare CTRL+C prontamente.
     */
    private function sleepWithInterrupt(int $seconds): void
    {
        for ($i = 0; $i < $seconds; $i++) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            if (! $this->running) {
                return;
            }

            sleep(1);
        }
    }

    private function registerSignalHandlers(): void
    {
        if (! function_exists('pcntl_signal')) {
            return;
        }

        pcntl_signal(SIGINT, function (): void {
            $this->running = false;
        });

        pcntl_signal(SIGTERM, function (): void {
            $this->running = false;
        });
    }
}
