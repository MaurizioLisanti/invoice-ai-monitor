<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration SOLO per il DB di sviluppo / test.
 *
 * [A1] In produzione la tabella invoices_queue esiste già —
 *      questa migration NON va eseguita sul DB reale di fatturazione.
 *      Usare `make migrate` SOLO su ambienti di sviluppo/test.
 *
 * [A5] Schema assunto — da verificare con il team prima del deploy.
 *      Se lo schema reale è diverso, creare una VIEW SQL read-only
 *      e impostare INVOICE_QUEUE_TABLE=nome_view in .env
 */
return new class extends Migration
{
    public function up(): void
    {
        // Crea solo se non esiste già (sicuro su DB condivisi)
        if (Schema::hasTable('invoices_queue')) {
            return;
        }

        Schema::create('invoices_queue', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_ref', 64)->nullable()->comment('Riferimento fattura SDI');
            $table->enum('status', ['pending', 'sent', 'accepted', 'rejected', 'error'])
                ->default('pending')
                ->comment('Stato corrente della fattura');
            $table->text('error_message')->nullable()->comment('Messaggio di errore SDI');
            $table->timestamps();

            // Indice per query ottimizzate [R3]
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices_queue');
    }
};
