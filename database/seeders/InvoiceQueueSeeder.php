<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder per popolamento dati di test.
 * Genera scenari per la verifica dei 3 stati del semaforo M0.
 *
 * Uso:
 *   SEED_SCENARIO=verde  make seed   → 5 pending,  0 errori  → VERDE
 *   SEED_SCENARIO=giallo make seed   → 12 pending, 4 errori  → GIALLO
 *   SEED_SCENARIO=rosso  make seed   → 55 pending, 11 errori → ROSSO
 */
class InvoiceQueueSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('invoices_queue')->truncate();

        $scenario = env('SEED_SCENARIO', 'verde');

        match ($scenario) {
            'giallo' => $this->seedGiallo(),
            'rosso' => $this->seedRosso(),
            default => $this->seedVerde(),
        };

        $this->command->info("Seeder completato: scenario '{$scenario}'");
    }

    /** 5 pending, 0 errori → VERDE */
    private function seedVerde(): void
    {
        $this->insertRows('pending', 5);
        $this->insertRows('accepted', 20);
        $this->insertRows('sent', 3);
    }

    /** 12 pending, 4 errori → GIALLO */
    private function seedGiallo(): void
    {
        $this->insertRows('pending', 12);
        $this->insertRows('error', 4);
        $this->insertRows('accepted', 15);
    }

    /** 55 pending, 11 errori → ROSSO */
    private function seedRosso(): void
    {
        $this->insertRows('pending', 55);
        $this->insertRows('error', 11);
        $this->insertRows('accepted', 10);
    }

    private function insertRows(string $status, int $count): void
    {
        $now = now();
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'invoice_ref' => strtoupper($status).'_'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'status' => $status,
                'error_message' => in_array($status, ['error', 'rejected'])
                    ? "Errore SDI: codice {$i} — documento non conforme al formato FatturaPA."
                    : null,
                'created_at' => $now->copy()->subMinutes($count - $i),
                'updated_at' => $now->copy()->subMinutes($count - $i),
            ];
        }

        DB::table('invoices_queue')->insert($rows);
    }
}
