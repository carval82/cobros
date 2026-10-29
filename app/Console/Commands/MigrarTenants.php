<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Support\Tenencia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class MigrarTenants extends Command
{
    protected $signature = 'cobros:migrar-tenants';

    protected $description = 'Aplica las migraciones de cartera en cada empresa';

    public function handle(): int
    {
        $empresas = Empresa::query()->whereNotNull('base_datos')->orderBy('id')->get();

        foreach ($empresas as $empresa) {
            try {
                Tenencia::conectar($empresa);
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);
                $this->info($empresa->nombre.' · '.$empresa->base_datos);
            } catch (Throwable $e) {
                $this->warn($empresa->nombre.': '.$e->getMessage());
            } finally {
                Tenencia::cerrar();
            }
        }

        return self::SUCCESS;
    }
}
