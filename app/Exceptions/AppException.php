<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Eccezione base del dominio invoice-ai-monitor.
 *
 * Tutti i servizi devono lanciare AppException (o sottoclassi) —
 * mai swallow eccezioni con catch vuoti. [AGENTS.md §5]
 */
class AppException extends \RuntimeException {}
