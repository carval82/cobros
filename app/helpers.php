<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

function cobro_route(string $name, mixed $parameters = [], bool $absolute = true): string
{
    $tenant = null;
    $porRuta = false;

    if (app()->bound('request')) {
        $tenant = request()->route('tenant') ?: request()->attributes->get('tenantId');
        $porRuta = (bool) request()->attributes->get('tenantPorRuta') || (bool) request()->route('tenant');
    }

    if ($porRuta && $tenant && Route::has('tenant.'.$name)) {
        $parameters = ['tenant' => $tenant] + Arr::wrap($parameters);

        return route('tenant.'.$name, $parameters, $absolute);
    }

    return route($name, $parameters, $absolute);
}
