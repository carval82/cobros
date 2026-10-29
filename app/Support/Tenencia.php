<?php

namespace App\Support;

use App\Models\Empresa;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\Facades\DB;

class Tenencia
{
    private static bool $activa = false;

    private static ?string $anterior = null;

    public static function activa(): bool
    {
        return self::$activa;
    }

    public static function conectar(Empresa $empresa): void
    {
        if (! $empresa->base_datos) {
            self::cerrar();

            return;
        }

        self::configurar($empresa->base_datos);

        if (! self::$activa) {
            self::$anterior = config('database.default');
        }

        config(['database.default' => 'tenant']);
        DB::setDefaultConnection('tenant');
        self::$activa = true;
    }

    public static function cerrar(): void
    {
        if (! self::$activa || ! self::$anterior) {
            self::$activa = false;

            return;
        }

        config(['database.default' => self::$anterior]);
        DB::setDefaultConnection(self::$anterior);
        self::$activa = false;
    }

    public static function configurar(string $nombre): void
    {
        $central = config('tenencia.central', config('database.default'));
        $base = config('database.connections.'.$central);

        if (! empty($base['url'])) {
            $base = (new ConfigurationUrlParser)->parseConfiguration($base);
        }

        $base['url'] = null;

        if (($base['driver'] ?? '') === 'sqlite') {
            $directorio = storage_path('framework/tenants');
            if (! is_dir($directorio)) {
                mkdir($directorio, 0777, true);
            }
            $ruta = $directorio.DIRECTORY_SEPARATOR.$nombre.'.sqlite';
            if (! is_file($ruta)) {
                touch($ruta);
            }
            $base['database'] = $ruta;
        } else {
            $base['database'] = $nombre;
        }

        config(['database.connections.tenant' => $base]);
        DB::purge('tenant');
    }
}
