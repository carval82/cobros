<?php

namespace App\Models\Concerns;

use App\Support\Tenencia;

trait UsaBaseTenant
{
    public function getConnectionName(): ?string
    {
        if (Tenencia::activa()) {
            return 'tenant';
        }

        return parent::getConnectionName();
    }
}
