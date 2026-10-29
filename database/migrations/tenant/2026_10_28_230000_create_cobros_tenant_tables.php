<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->string('nombre');
            $table->string('documento')->nullable();
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();
            $table->text('notas')->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->timestamps();
            $table->index('nombre');
            $table->index('documento');
        });

        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('capital');
            $table->decimal('porcentaje', 8, 2);
            $table->unsignedBigInteger('interes');
            $table->unsignedBigInteger('total');
            $table->unsignedSmallInteger('numero_cuotas');
            $table->string('frecuencia', 20);
            $table->date('fecha_primer_pago');
            $table->text('nota')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamp('detenido_en')->nullable();
            $table->string('motivo_detencion')->nullable();
            $table->timestamps();
            $table->index('estado');
        });

        Schema::create('cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->date('fecha_vencimiento');
            $table->unsignedBigInteger('capital');
            $table->unsignedBigInteger('interes');
            $table->unsignedBigInteger('valor');
            $table->unsignedBigInteger('pagado')->default(0);
            $table->unsignedBigInteger('saldo');
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();
            $table->unique(['prestamo_id', 'numero']);
            $table->index(['fecha_vencimiento', 'estado']);
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->foreignId('prestamo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('monto');
            $table->date('fecha');
            $table->string('nota')->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->timestamps();
            $table->index('fecha');
        });

        Schema::create('pago_cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cuota_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('monto');
            $table->timestamps();
        });

        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prestamo_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tipo', 30);
            $table->string('descripcion', 500);
            $table->unsignedBigInteger('monto')->nullable();
            $table->date('fecha');
            $table->timestamps();
            $table->index(['cliente_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
        Schema::dropIfExists('pago_cuotas');
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('cuotas');
        Schema::dropIfExists('prestamos');
        Schema::dropIfExists('clientes');
    }
};
