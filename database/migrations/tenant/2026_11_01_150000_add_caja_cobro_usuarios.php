<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_cobro_usuarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->foreignId('caja_cobro_id')->constrained('caja_cobros')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
            $table->unique(['caja_cobro_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_cobro_usuarios');
    }
};
