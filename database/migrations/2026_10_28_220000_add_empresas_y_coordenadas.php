<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('es_maestro')->default(false);
            $table->foreignId('empresa_id')->nullable()->after('es_maestro')->constrained('empresas')->nullOnDelete();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('id')->constrained('empresas')->restrictOnDelete();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->index('empresa_id');
        });

        Schema::table('prestamos', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('cliente_id')->constrained('empresas')->restrictOnDelete();
            $table->index('empresa_id');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('cliente_id')->constrained('empresas')->restrictOnDelete();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->index('empresa_id');
        });

        Schema::table('movimientos', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('cliente_id')->constrained('empresas')->restrictOnDelete();
            $table->index('empresa_id');
        });

        $empresaId = DB::table('empresas')->insertGetId([
            'nombre' => 'Principal',
            'slug' => 'principal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('clientes')->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);

        $empresasPorCliente = DB::table('clientes')->pluck('empresa_id', 'id');

        foreach (DB::table('prestamos')->whereNull('empresa_id')->get(['id', 'cliente_id']) as $prestamo) {
            $id = $empresasPorCliente[$prestamo->cliente_id] ?? null;
            if ($id) {
                DB::table('prestamos')->where('id', $prestamo->id)->update(['empresa_id' => $id]);
            }
        }

        foreach (DB::table('pagos')->whereNull('empresa_id')->get(['id', 'cliente_id']) as $pago) {
            $id = $empresasPorCliente[$pago->cliente_id] ?? null;
            if ($id) {
                DB::table('pagos')->where('id', $pago->id)->update(['empresa_id' => $id]);
            }
        }

        foreach (DB::table('movimientos')->whereNull('empresa_id')->get(['id', 'cliente_id']) as $movimiento) {
            $id = $empresasPorCliente[$movimiento->cliente_id] ?? null;
            if ($id) {
                DB::table('movimientos')->where('id', $movimiento->id)->update(['empresa_id' => $id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
        });
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
            $table->dropColumn(['latitud', 'longitud']);
        });
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
        });
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
            $table->dropColumn(['latitud', 'longitud']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
            $table->dropColumn('es_maestro');
        });
        Schema::dropIfExists('empresas');
    }
};
