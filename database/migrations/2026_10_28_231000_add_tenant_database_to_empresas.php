<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('base_datos')->nullable()->unique();
            $table->string('email')->nullable();
            $table->boolean('activo')->default(true);
            $table->string('plan', 20)->default('demo');
            $table->string('codigo_solicitud', 32)->nullable();
            $table->timestamp('fecha_vencimiento')->nullable();
            $table->timestamp('licencia_activada_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'base_datos',
                'email',
                'activo',
                'plan',
                'codigo_solicitud',
                'fecha_vencimiento',
                'licencia_activada_at',
            ]);
        });
    }
};
