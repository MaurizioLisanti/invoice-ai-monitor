<?php

declare(strict_types=1);

namespace App\Services;

class LogReaderService
{
    private string $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs/laravel.log');
    }

    /**
     * Restituisce le ultime $n voci di log parsate dal file JSON.
     * La voce più recente è la prima dell'array restituito.
     * Righe non-JSON (stack trace, ecc.) vengono silenziosamente ignorate.
     *
     * @return array<int, array{message: string, level_name: string, datetime: string, context: array<string, mixed>}>
     */
    public function tail(int $n = 50): array
    {
        if (! file_exists($this->logPath)) {
            return [];
        }

        $lines = file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false || count($lines) === 0) {
            return [];
        }

        $lines = array_slice($lines, -$n);

        $entries = [];

        foreach ($lines as $line) {
            $decoded = json_decode($line, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $entries[] = [
                    'message' => (string) ($decoded['message'] ?? ''),
                    'level_name' => (string) ($decoded['level_name'] ?? 'UNKNOWN'),
                    'datetime' => (string) ($decoded['datetime'] ?? ''),
                    'context' => (array) ($decoded['context'] ?? []),
                ];
            }
        }

        return array_reverse($entries);
    }
}
