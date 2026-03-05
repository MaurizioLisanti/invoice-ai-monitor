<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\AppException;
use App\Services\TrafficLightService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckInvoiceQueueCommand extends Command
{
    protected $signature = 'invoice:check-queue';

    protected $description = 'Controlla lo stato della coda fatture e registra il risultato nel log';

    public function __construct(private readonly TrafficLightService $trafficLight)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $snapshot = $this->trafficLight->snapshot();
        } catch (AppException $e) {
            Log::error('queue_check_failed', [
                'service' => 'CheckInvoiceQueueCommand',
                'error' => $e->getMessage(),
                'ts' => now()->toIso8601String(),
            ]);

            return self::FAILURE;
        }

        Log::info('queue_check', [
            'status' => $snapshot['status'],
            'pending' => $snapshot['pending'],
            'errors' => $snapshot['errors'],
            'ts' => now()->toIso8601String(),
        ]);

        if ($snapshot['status'] === 'red') {
            Log::critical('queue_alert_red', [
                'pending' => $snapshot['pending'],
                'errors' => $snapshot['errors'],
                'ts' => now()->toIso8601String(),
            ]);
        }

        return self::SUCCESS;
    }
}
