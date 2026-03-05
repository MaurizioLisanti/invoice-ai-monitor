<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Model read-only sulla tabella (o view) della coda fatture.
 *
 * [A1] La tabella esiste già sul DB di fatturazione — questa classe
 *      è puramente in lettura (nessuna migration distruttiva in prod).
 * [A5] Schema assunto: id, invoice_ref, status, error_message,
 *      created_at, updated_at — verificare con il team prima del deploy.
 */
class InvoiceQueue extends Model
{
    /** Read-only: nessuna scrittura dalla dashboard. */
    protected $fillable = [];

    /**
     * Nome tabella configurabile via env/config per adattarsi allo schema reale. [A5]
     * Valore: config('invoice.queue_table') → env('INVOICE_QUEUE_TABLE', 'invoices_queue')
     */
    public function getTable(): string
    {
        return config('invoice.queue_table', 'invoices_queue');
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Scopes ──────────────────────────────────────────────────

    /** Fatture in attesa di invio. */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /** Fatture in stato di errore o rifiutate. */
    public function scopeErrors(Builder $query): Builder
    {
        return $query->whereIn('status', ['error', 'rejected']);
    }

    /** Ultimi N record per la tabella eventi. */
    public function scopeRecent(Builder $query, int $limit = 20): Builder
    {
        return $query->orderByDesc('updated_at')->limit($limit);
    }
}
