<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->integer('precio'); // mensualidad en centavos (MXN)
            $table->text('descripcion')->nullable();
            $table->string('datos')->nullable();         // ej. "8 GB" / "Ilimitado"
            $table->string('minutos')->nullable();       // ej. "Ilimitados"
            $table->string('sms')->nullable();           // ej. "500" / "Ilimitados"
            $table->string('tipo')->default('prepago');  // prepago | pospago
            $table->integer('vigencia_dias')->default(30);
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};
