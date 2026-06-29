<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_contact_id')->nullable()->constrained('bot_contacts')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('planes')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('numero_id')->nullable()->constrained('numeros')->nullOnDelete();
            $table->string('cliente')->nullable();
            $table->string('telefono');
            $table->string('email')->nullable();
            $table->integer('total')->default(0);             // centavos (MXN)
            $table->string('metodo_pago')->nullable();        // stripe | paypal | spei
            $table->string('referencia_pago')->nullable();    // link/folio de pago
            $table->string('estado')->default('iniciado');    // iniciado|en_pago|pagado|numero_asignado|entregado|cancelado
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
