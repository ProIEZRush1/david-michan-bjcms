<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario de números (líneas) disponibles para asignar. Al confirmarse un
 * pago, el sistema toma un número 'disponible' y lo asigna automáticamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numeros', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->string('lada')->nullable();          // código de área / ciudad
            $table->string('tipo')->default('movil');    // movil | fijo
            $table->string('estado')->default('disponible'); // disponible | reservado | asignado | baja
            $table->foreignId('plan_id')->nullable()->constrained('planes')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numeros');
    }
};
