<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_cobros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('nombre');
            $table->string('tipo', 20)->default('cobrador');
            $table->unsignedBigInteger('capital')->default(0);
            $table->boolean('abierto')->default(true);
            $table->timestamps();
        });

        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->foreignId('caja_cobro_id')->constrained('caja_cobros')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('monto');
            $table->date('fecha');
            $table->string('nota')->nullable();
            $table->timestamps();
        });

        Schema::table('prestamos', function (Blueprint $table) {
            $table->unsignedBigInteger('caja_cobro_id')->nullable()->index();
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->unsignedBigInteger('caja_cobro_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('caja_cobro_id');
        });
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropColumn('caja_cobro_id');
        });
        Schema::dropIfExists('gastos');
        Schema::dropIfExists('caja_cobros');
    }
};
