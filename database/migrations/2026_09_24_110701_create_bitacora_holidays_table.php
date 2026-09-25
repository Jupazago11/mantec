<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Festivos marcados a mano (pedido 2026-09-24): el sistema no tiene un
 * calendario real de festivos colombianos (BitacoraController ya lo
 * dejaba anotado, solo marcaba domingos), asi que un administrativo
 * puede marcar/quitar un dia puntual como festivo desde Bitacora
 * (clic en el numero del dia) para que se pinte en rojo igual que un
 * domingo. `date` es la fecha exacta (no un dia-del-año recurrente):
 * un festivo movible (ley Emiliani) no cae en la misma fecha cada año,
 * asi que marcar julio 20 de 2026 no marca julio 20 de 2027.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->foreignId('created_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_holidays');
    }
};
