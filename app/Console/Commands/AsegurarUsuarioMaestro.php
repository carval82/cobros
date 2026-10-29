<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AsegurarUsuarioMaestro extends Command
{
    protected $signature = 'cobros:usuario-maestro';

    protected $description = 'Deja listo el usuario maestro de acceso';

    public function handle(): int
    {
        User::query()->updateOrCreate(
            ['email' => 'pcapacho24@gmail.com'],
            [
                'name' => 'Master',
                'password' => 'anaval33',
            ]
        );

        $this->info('Usuario maestro listo.');

        return self::SUCCESS;
    }
}
