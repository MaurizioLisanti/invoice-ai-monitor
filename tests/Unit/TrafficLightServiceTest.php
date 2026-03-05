<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TrafficLightService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test unitari per TrafficLightService::computeStatus().
 *
 * Copertura: 5 combinazioni (3 scenari semaforo richiesti da BOARD DoD update):
 *   - VERDE  (pending≤10, errors≤3)
 *   - GIALLO (pending>10 oppure errors>3)
 *   - ROSSO  (pending>50 oppure errors>10)
 *
 * computeStatus() è logica pura — nessun DB necessario.
 * Soglie lette da config/invoice.php (default: yellow 10/3, red 50/10).
 */
class TrafficLightServiceTest extends TestCase
{
    private TrafficLightService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrafficLightService;
    }

    // ── Scenario VERDE ────────────────────────────────────────────

    #[Test]
    public function compute_status_returns_green_when_within_thresholds(): void
    {
        // pending=5 (≤10), errors=0 (≤3) → VERDE
        $this->assertSame('green', $this->service->computeStatus(5, 0));
    }

    // ── Scenario GIALLO ───────────────────────────────────────────

    #[Test]
    public function compute_status_returns_yellow_when_pending_exceeds_threshold(): void
    {
        // pending=15 (>10), errors=0 → GIALLO
        $this->assertSame('yellow', $this->service->computeStatus(15, 0));
    }

    #[Test]
    public function compute_status_returns_yellow_when_errors_exceed_threshold(): void
    {
        // pending=0, errors=5 (>3) → GIALLO
        $this->assertSame('yellow', $this->service->computeStatus(0, 5));
    }

    // ── Scenario ROSSO ────────────────────────────────────────────

    #[Test]
    public function compute_status_returns_red_when_pending_exceeds_red_threshold(): void
    {
        // pending=55 (>50) → ROSSO
        $this->assertSame('red', $this->service->computeStatus(55, 0));
    }

    #[Test]
    public function compute_status_returns_red_when_errors_exceed_red_threshold(): void
    {
        // errors=12 (>10) → ROSSO
        $this->assertSame('red', $this->service->computeStatus(0, 12));
    }
}
