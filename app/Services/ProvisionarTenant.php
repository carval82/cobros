<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\User;
use App\Support\Tenencia;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProvisionarTenant
{
    public function aprovisionar(Empresa $empresa, string $nombreUsuario, string $email, string $password): User
    {
        $base = 'cobros_e'.$empresa->id;
        $empresa->forceFill([
            'base_datos' => $base,
            'codigo_solicitud' => $empresa->codigo_solicitud ?: ActivacionLicencia::codigoSolicitud('cobros-'.$empresa->id.'-'.$empresa->slug),
            'activo' => true,
            'plan' => $empresa->plan ?: 'demo',
            'fecha_vencimiento' => $empresa->fecha_vencimiento ?: now()->addDays(30),
        ])->save();

        $this->crearBase($base);
        Tenencia::configurar($base);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        return User::query()->create([
            'name' => $nombreUsuario,
            'email' => $email,
            'password' => $password,
            'empresa_id' => $empresa->id,
            'es_maestro' => false,
            'rol' => 'administrador',
        ]);
    }

    private function crearBase(string $nombre): void
    {
        if (! preg_match('/^cobros_e[0-9]+$/', $nombre)) {
            throw new RuntimeException('El nombre de la base no es válido.');
        }

        $central = config('tenencia.central', config('database.default'));
        $driver = config('database.connections.'.$central.'.driver');

        if ($driver === 'sqlite') {
            Tenencia::configurar($nombre);

            return;
        }

        DB::connection($central)->statement(
            "CREATE DATABASE IF NOT EXISTS `{$nombre}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }
}
