<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\LogReaderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test smoke per TASK_01_log_viewer_m2.
 * Copertura:
 *   ✓ GET /logs risponde HTTP 200
 *   ✓ Vista mostra "Nessun log disponibile" se service restituisce []
 *   ✓ Vista mostra le righe quando il service ha dati
 */
class LogViewerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function log_viewer_risponde_200(): void
    {
        $this->mock(LogReaderService::class, function ($mock) {
            $mock->shouldReceive('tail')->andReturn([]);
        });

        $this->get('/logs')->assertOk();
    }

    #[Test]
    public function log_viewer_mostra_messaggio_se_nessun_log(): void
    {
        $this->mock(LogReaderService::class, function ($mock) {
            $mock->shouldReceive('tail')->andReturn([]);
        });

        $this->get('/logs')
            ->assertOk()
            ->assertSee('Nessun log disponibile');
    }

    #[Test]
    public function log_viewer_mostra_voci_di_log(): void
    {
        $this->mock(LogReaderService::class, function ($mock) {
            $mock->shouldReceive('tail')->andReturn([
                [
                    'message' => 'queue_check',
                    'level_name' => 'INFO',
                    'datetime' => '2026-03-02T10:00:00.000000+01:00',
                    'context' => ['status' => 'green', 'pending' => 3],
                ],
                [
                    'message' => 'snapshot_failed',
                    'level_name' => 'ERROR',
                    'datetime' => '2026-03-02T10:01:00.000000+01:00',
                    'context' => ['error' => 'connection refused'],
                ],
            ]);
        });

        $this->get('/logs')
            ->assertOk()
            ->assertSee('queue_check')
            ->assertSee('INFO')
            ->assertSee('snapshot_failed')
            ->assertSee('ERROR');
    }

    #[Test]
    public function log_reader_service_tail_restituisce_array(): void
    {
        // Test diretto del service: gestisce file assente senza eccezioni
        $service = app(LogReaderService::class);
        $result = $service->tail(50);

        $this->assertIsArray($result);
    }
}
