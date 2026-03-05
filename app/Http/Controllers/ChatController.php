<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AppException;
use App\Services\ChatService;
use App\Services\TrafficLightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function ask(Request $request, TrafficLightService $trafficLight, ChatService $chat): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        try {
            $snapshot = $trafficLight->snapshot();
            $reply = $chat->ask($snapshot, $validated['question']);

            return response()->json(['reply' => $reply]);
        } catch (AppException $e) {
            Log::error('chat_error', [
                'service' => 'ChatController',
                'operation' => 'ask',
                'error' => $e->getMessage(),
                'ts' => now()->toISOString(),
            ]);

            return response()->json(['error' => 'Servizio AI temporaneamente non disponibile.'], 503);
        }
    }
}
