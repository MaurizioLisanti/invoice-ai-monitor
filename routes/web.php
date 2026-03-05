<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| invoice-ai-monitor — Web Routes
|--------------------------------------------------------------------------
| Tutte le route sono GET (dashboard read-only, nessuna mutazione dati).
| Nessuna autenticazione nell'MVP [A2] — proteggere via rete aziendale/VPN.
*/

// Dashboard principale
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Polling JSON — aggiorna semaforo e contatori
Route::get('/status', [DashboardController::class, 'status'])->name('dashboard.status');

// "Spiegami" — spiegazione in italiano [A3]
Route::get('/explain', [DashboardController::class, 'explain'])->name('dashboard.explain');

// Log viewer — ultime 50 voci di log JSON [M2]
Route::get('/logs', [DashboardController::class, 'logs'])->name('logs');

// Chat AI — risposta contestualizzata Gemini [M2]
Route::post('/chat', [ChatController::class, 'ask'])->name('chat.ask');
