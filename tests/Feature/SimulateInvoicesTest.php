<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SimulateInvoicesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function simulator_esegue_un_ciclo_green_e_inserisce_fatture(): void
    {
        $this->artisan('invoice:simulate', [
            '--scenario' => 'green',
            '--max-cycles' => 1,
        ])->assertExitCode(0);

        $count = DB::table('invoices_queue')->count();

        $this->assertGreaterThanOrEqual(3, $count);
        $this->assertLessThanOrEqual(5, $count);
    }

    #[Test]
    public function simulator_processa_pending_piu_vecchi_di_60_secondi(): void
    {
        // Pre-seed: 5 fatture pending già "vecchie" di 90s
        DB::table('invoices_queue')->insert(array_map(fn ($i) => [
            'invoice_ref' => "FT-2026-OLD-{$i}",
            'status' => 'pending',
            'error_message' => null,
            'created_at' => now()->subSeconds(90),
            'updated_at' => now()->subSeconds(90),
        ], range(1, 5)));

        $this->artisan('invoice:simulate', [
            '--scenario' => 'green',
            '--max-cycles' => 1,
        ])->assertExitCode(0);

        // Le 5 righe vecchie devono essere passate ad accepted o error
        $stillPending = DB::table('invoices_queue')
            ->where('status', 'pending')
            ->where('invoice_ref', 'like', 'FT-2026-OLD-%')
            ->count();

        $this->assertSame(0, $stillPending);
    }

    #[Test]
    public function simulator_scenario_red_non_processa_pending(): void
    {
        // Pre-seed: 3 pending vecchie
        DB::table('invoices_queue')->insert(array_map(fn ($i) => [
            'invoice_ref' => "FT-2026-SKIP-{$i}",
            'status' => 'pending',
            'error_message' => null,
            'created_at' => now()->subSeconds(90),
            'updated_at' => now()->subSeconds(90),
        ], range(1, 3)));

        $this->artisan('invoice:simulate', [
            '--scenario' => 'red',
            '--max-cycles' => 1,
        ])->assertExitCode(0);

        // In scenario red il processing è sospeso: le righe restano pending
        $stillPending = DB::table('invoices_queue')
            ->where('status', 'pending')
            ->where('invoice_ref', 'like', 'FT-2026-SKIP-%')
            ->count();

        $this->assertSame(3, $stillPending);
    }

    #[Test]
    public function simulator_scenario_red_accumula_piu_fatture_per_ciclo(): void
    {
        $this->artisan('invoice:simulate', [
            '--scenario' => 'red',
            '--max-cycles' => 1,
        ])->assertExitCode(0);

        $count = DB::table('invoices_queue')->count();

        // Red inserisce 25-35 per ciclo
        $this->assertGreaterThanOrEqual(25, $count);
        $this->assertLessThanOrEqual(35, $count);
    }

    #[Test]
    public function simulator_fallisce_con_scenario_non_valido(): void
    {
        $this->artisan('invoice:simulate', [
            '--scenario' => 'arancione',
            '--max-cycles' => 1,
        ])->assertExitCode(1);
    }

    #[Test]
    public function simulator_logga_ciclo_con_campi_corretti(): void
    {
        Log::spy();

        $this->artisan('invoice:simulate', [
            '--scenario' => 'yellow',
            '--max-cycles' => 1,
        ])->assertExitCode(0);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'simulator_cycle'
                    && isset($context['cycle'], $context['scenario'], $context['inserted'],
                        $context['accepted'], $context['errors'], $context['ts'])
                    && $context['scenario'] === 'yellow';
            });
    }

    #[Test]
    public function simulator_invoice_ref_segue_formato_atteso(): void
    {
        $this->artisan('invoice:simulate', [
            '--scenario' => 'green',
            '--max-cycles' => 1,
        ])->assertExitCode(0);

        $refs = DB::table('invoices_queue')->pluck('invoice_ref');

        foreach ($refs as $ref) {
            $this->assertMatchesRegularExpression(
                '/^FT-\d{4}-[A-Z_]+-\d{4}$/',
                $ref,
                "invoice_ref '{$ref}' non rispetta il formato FT-{anno}-{COGNOME}-{seq}"
            );
        }
    }
}
