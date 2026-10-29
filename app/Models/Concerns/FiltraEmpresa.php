<?php

namespace App\Models\Concerns;

use App\Support\EmpresaActual;
use Illuminate\Database\Eloquent\Builder;

trait FiltraEmpresa
{
    protected static function bootFiltraEmpresa(): void
    {
        static::addGlobalScope('empresa', function (Builder $query) {
            if (! EmpresaActual::restringe()) {
                return;
            }

            $query->where($query->getModel()->getTable().'.empresa_id', EmpresaActual::id() ?? 0);
        });

        static::creating(function ($modelo) {
            if ($modelo->empresa_id || ! EmpresaActual::restringe()) {
                return;
            }

            $modelo->empresa_id = EmpresaActual::id();
        });
    }
}
