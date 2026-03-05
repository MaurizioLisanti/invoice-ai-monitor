<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Models\InvoiceQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Calcola lo stato semaforo (green / yellow / red)
 * in base alle soglie configurate via .env.
 *
 * Soglie (configurabili — [A6]):
 *   VERDE  → pending ≤ YELLOW_PENDING  AND  errori ≤ YELLOW_ERRORS
 *   GIALLO → pending > YELLOW_PENDING  OR   errori > YELLOW_ERRORS
 *   ROSSO  → pending > RED_PENDING     OR   errori > RED_ERRORS
 */
class TrafficLightService
{
    private readonly int $yellowPending;

    private readonly int $yellowErrors;

    private readonly int $redPending;

    private readonly int $redErrors;

    public function __construct()
    {
        $this->yellowPending = (int) config('invoice.semaforo_yellow_pending', 10);
        $this->yellowErrors = (int) config('invoice.semaforo_yellow_errors', 3);
        $this->redPending = (int) config('invoice.semaforo_red_pending', 50);
        $this->redErrors = (int) config('invoice.semaforo_red_errors', 10);
    }

    /**
     * Restituisce lo snapshot corrente con stato semaforo e contatori.
     * Il risultato è cachato per `cache_ttl_seconds` per ridurre le query al DB. [R3]
     *
     * @return array{status: string, pending: int, errors: int, last_updated: string}
     */
    public function snapshot(): array
    {
        $ttl = (int) config('invoice.cache_ttl_seconds', 30);

        return Cache::remember('semaforo_snapshot', $ttl, function () {
            try {
                $pending = InvoiceQueue::pending()->count();
                $errors = InvoiceQueue::errors()->count();

                return [
                    'status' => $this->computeStatus($pending, $errors),
                    'pending' => $pending,
                    'errors' => $errors,
                    'last_updated' => now()->toIso8601String(),
                ];
            } catch (\Throwable $e) {
                Log::error('snapshot_failed', [
                    'service' => 'TrafficLightService',
                    'operation' => 'snapshot',
                    'error' => $e->getMessage(),
                    'ts' => now()->toIso8601String(),
                ]);

                throw new AppException('Impossibile leggere lo stato semaforo', 0, $e);
            }
        });
    }

    /**
     * Restituisce gli ultimi N eventi per la tabella dashboard.
     *
     * @return \Illuminate\Database\Eloquent\Collection<InvoiceQueue>
     */
    public function recentEvents(int $limit = 20)
    {
        return InvoiceQueue::recent($limit)->get();
    }

    public function computeStatus(int $pending, int $errors): string
    {
        if ($pending > $this->redPending || $errors > $this->redErrors) {
            return 'red';
        }

        if ($pending > $this->yellowPending || $errors > $this->yellowErrors) {
            return 'yellow';
        }

        return 'green';
    }
}
