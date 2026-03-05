<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AppException;
use App\Services\ExplainerService;
use App\Services\LogReaderService;
use App\Services\TrafficLightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TrafficLightService $trafficLight,
        private readonly ExplainerService $explainer,
    ) {}

    /**
     * Mostra la dashboard principale con semaforo e tabella eventi.
     * Il polling lato client aggiorna i dati ogni DASHBOARD_REFRESH_SECONDS. [A6]
     * Se il DB non è raggiungibile (AppException), risponde 503. [M2]
     */
    public function index(): View
    {
        try {
            $snapshot = $this->cachedSnapshot();
            $events = $this->trafficLight->recentEvents(20);
        } catch (AppException) {
            abort(503);
        }

        return view('dashboard', [
            'snapshot' => $snapshot,
            'events' => $events,
            'refreshSeconds' => (int) config('invoice.dashboard_refresh_seconds', 60),
        ]);
    }

    /**
     * Endpoint JSON per il polling AJAX — aggiorna semaforo e contatori.
     * Risposta esempio: {"status":"red","pending":55,"errors":12,"last_updated":"..."}
     */
    public function status(): JsonResponse
    {
        try {
            return response()->json($this->cachedSnapshot());
        } catch (AppException) {
            return response()->json(['error' => 'Servizio temporaneamente non disponibile'], 503);
        }
    }

    /**
     * Endpoint "Spiegami" — genera testo in italiano plain-language. [A3]
     * Risposta esempio: {"status":"red","explanation":"PROBLEMA CRITICO: ..."}
     */
    public function explain(): JsonResponse
    {
        try {
            $snapshot = $this->cachedSnapshot();
            $explanation = $this->explainer->explain($snapshot);

            return response()->json([
                'status' => $snapshot['status'],
                'stato' => $explanation['stato'],
                'diagnosi' => $explanation['diagnosi'],
                'azione' => $explanation['azione'],
            ]);
        } catch (AppException) {
            return response()->json(['error' => 'Servizio temporaneamente non disponibile'], 503);
        }
    }

    /**
     * Visualizza le ultime 50 voci di log JSON per il debug/monitoring. [M2]
     */
    public function logs(LogReaderService $logReader): View
    {
        $entries = $logReader->tail(50);

        return view('logs', ['entries' => $entries]);
    }

    /**
     * Snapshot con cache TTL configurabile.
     * Riduce query ridondanti al MySQL sotto carico (max 10 utenti simultanei). [R3]
     *
     * @return array{status: string, pending: int, errors: int, last_updated: string}
     */
    private function cachedSnapshot(): array
    {
        $ttl = (int) config('invoice.cache_ttl_seconds', 30);

        return Cache::remember('semaforo_snapshot', $ttl, function () {
            return $this->trafficLight->snapshot();
        });
    }
}
