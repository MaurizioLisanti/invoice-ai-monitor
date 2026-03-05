<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\AppException;
use App\Services\TrafficLightService;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test unitari per CheckInvoiceQueueCommand (invoice:check-queue).
 *
 * TrafficLightService è mockato — nessun DB necessario.
 * Log è spiato con Log::spy() per verificare i messaggi emessi.
 *
 * Copertura:
 *   - Stato normale (green): exit 0 + Log::info 'queue_check'
 *   - Stato critico (red):   exit 0 + Log::info + Log::critical 'queue_alert_red'
 *   - AppException:          exit 1 + Log::error 'queue_check_failed'
 */
class CheckInvoiceQueueCommandTest extends TestCase
{
    #[Test]
    public function command_exits_successfully_and_logs_queue_check(): void
    {
        $this->mock(TrafficLightService::class, function ($mock): void {
            $mock->shouldReceive('snapshot')->once()->andReturn([
                'status' => 'green',
                'pending' => 3,
                'errors' => 0,
                'last_updated' => now()->toDateTimeString(),
            ]);
        });

        Log::spy();

        $this->artisan('invoice:check-queue')->assertExitCode(0);

        Log::shouldHaveReceived('info')->once();
    }

    #[Test]
    public function command_logs_critical_alert_when_status_is_red(): void
    {
        $this->mock(TrafficLightService::class, function ($mock): void {
            $mock->shouldReceive('snapshot')->once()->andReturn([
                'status' => 'red',
                'pending' => 55,
                'errors' => 12,
                'last_updated' => now()->toDateTimeString(),
            ]);
        });

        Log::spy();

        $this->artisan('invoice:check-queue')->assertExitCode(0);

        Log::shouldHaveReceived('info')->once();
        Log::shouldHaveReceived('critical')->once();
    }

    #[Test]
    public function command_exits_with_failure_on_app_exception(): void
    {
        $this->mock(TrafficLightService::class, function ($mock): void {
            $mock->shouldReceive('snapshot')->once()->andThrow(
                new AppException('DB non raggiungibile')
            );
        });

        Log::spy();

        $this->artisan('invoice:check-queue')->assertExitCode(1);

        Log::shouldHaveReceived('error')->once();
    }
}
