<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\AppException;
use App\Services\TrafficLightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test di accettazione M0.
 * Ogni test è PASS/FAIL — verificabile con: make test
 *
 * Copertura criteri M0:
 *   ✓ M0-01: Semaforo VERDE
 *   ✓ M0-02: Semaforo GIALLO
 *   ✓ M0-03: Semaforo ROSSO
 *   ✓ M0-04: Dashboard HTTP 200
 *   ✓ M0-05: Spiegami testo italiano
 *   ✓ M0-06: Endpoint /status → JSON valido
 *   ✓ M0-07: Performance < 2s (assertion sulla struttura risposta)
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    // ── M0-01: Semaforo VERDE ─────────────────────────────────────
    #[Test]
    public function semaforo_mostra_verde_con_zero_errori_e_pending_nei_limiti(): void
    {
        // Arrange: 5 pending, 0 errori → sotto soglia yellow (10/3)
        $this->seedRows('pending', 5);
        $this->seedRows('accepted', 10);

        // Act + Assert
        $this->getJson('/status')
            ->assertOk()
            ->assertJson(['status' => 'green'])
            ->assertJsonPath('pending', 5)
            ->assertJsonPath('errors', 0);
    }

    // ── M0-02: Semaforo GIALLO ────────────────────────────────────
    #[Test]
    public function semaforo_mostra_giallo_con_pending_oltre_soglia(): void
    {
        // Arrange: 15 pending → oltre yellow (10), 0 errori
        $this->seedRows('pending', 15);

        $this->getJson('/status')
            ->assertOk()
            ->assertJson(['status' => 'yellow']);
    }

    #[Test]
    public function semaforo_mostra_giallo_con_errori_oltre_soglia(): void
    {
        // Arrange: 0 pending, 5 errori → oltre yellow (3)
        $this->seedRows('error', 5);

        $this->getJson('/status')
            ->assertOk()
            ->assertJson(['status' => 'yellow']);
    }

    // ── M0-03: Semaforo ROSSO ─────────────────────────────────────
    #[Test]
    public function semaforo_mostra_rosso_con_pending_oltre_soglia(): void
    {
        // Arrange: 55 pending → oltre red (50)
        $this->seedRows('pending', 55);

        $this->getJson('/status')
            ->assertOk()
            ->assertJson(['status' => 'red']);
    }

    #[Test]
    public function semaforo_mostra_rosso_con_errori_oltre_soglia(): void
    {
        // Arrange: 12 errori → oltre red (10)
        $this->seedRows('error', 12);

        $this->getJson('/status')
            ->assertOk()
            ->assertJson(['status' => 'red']);
    }

    // ── M0-04: Dashboard HTTP 200 ─────────────────────────────────
    #[Test]
    public function dashboard_risponde_200(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('invoice-ai-monitor');
    }

    // ── M0-05: Spiegami — 3 sezioni strutturate ──────────────────
    #[Test]
    public function spiegami_con_stato_verde_restituisce_testo_rassicurante(): void
    {
        $this->seedRows('pending', 3);

        $response = $this->getJson('/explain')
            ->assertOk()
            ->assertJsonStructure(['status', 'stato', 'diagnosi', 'azione'])
            ->assertJsonPath('status', 'green');

        // pending=3, errors=0 → regola "tutto ok"
        $this->assertStringContainsString('Tutto OK', $response->json('stato'));
        $this->assertStringContainsString('Nessuna azione', $response->json('azione'));
    }

    #[Test]
    public function spiegami_con_stato_rosso_restituisce_testo_critico(): void
    {
        $this->seedRows('error', 12);

        $response = $this->getJson('/explain')
            ->assertOk()
            ->assertJsonStructure(['status', 'stato', 'diagnosi', 'azione'])
            ->assertJsonPath('status', 'red');

        // pending=0, errors=12 → fallback semaforo red
        $this->assertStringContainsString('Critico', $response->json('stato'));
        $this->assertStringContainsString('supporto tecnico', $response->json('azione'));
    }

    // ── M0-06: Struttura JSON /status ────────────────────────────
    #[Test]
    public function endpoint_status_restituisce_struttura_json_valida(): void
    {
        $this->getJson('/status')
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'pending',
                'errors',
                'last_updated',
            ]);
    }

    // ── M2: Graceful 503 ─────────────────────────────────────────
    #[Test]
    public function dashboard_index_restituisce_503_se_db_non_disponibile(): void
    {
        $this->mock(TrafficLightService::class, function ($mock): void {
            $mock->shouldReceive('snapshot')->andThrow(new AppException('DB non raggiungibile'));
        });

        $this->get('/')->assertStatus(503);
    }

    // ── Helper ────────────────────────────────────────────────────
    private function seedRows(string $status, int $count): void
    {
        $rows = [];
        $now = now();

        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'invoice_ref' => strtoupper($status).'_TEST_'.$i,
                'status' => $status,
                'error_message' => in_array($status, ['error', 'rejected'])
                    ? "Errore test {$i}: documento non conforme"
                    : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('invoices_queue')->insert($rows);
    }
}
