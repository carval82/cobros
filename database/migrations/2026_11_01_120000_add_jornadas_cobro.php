<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jornadas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->foreignId('caja_cobro_id')->constrained('caja_cobros')->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedBigInteger('valor_inicial');
            $table->unsignedBigInteger('refuerzo')->default(0);
            $table->unsignedBigInteger('prestado')->default(0);
            $table->unsignedBigInteger('recaudado')->default(0);
            $table->unsignedBigInteger('gastos')->default(0);
            $table->unsignedBigInteger('interes')->default(0);
            $table->bigInteger('ganancia')->default(0);
            $table->bigInteger('cierre')->default(0);
            $table->string('decision', 20)->nullable();
            $table->unsignedBigInteger('retiro')->default(0);
            $table->bigInteger('valor_siguiente')->nullable();
            $table->boolean('cerrada')->default(false);
            $table->timestamps();
            $table->unique(['caja_cobro_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jornadas');
    }
};
