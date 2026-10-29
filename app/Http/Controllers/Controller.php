<?php

namespace App\Http\Controllers;

use ReflectionMethod;
use ReflectionNamedType;

abstract class Controller
{
    /**
     * La dirección del tenant agrega {tenant} a la ruta. El controlador
     * solo recibe los argumentos que su método declara.
     */
    public function callAction($method, $parameters)
    {
        $metodo = new ReflectionMethod($this, $method);
        $aceptados = [];

        foreach ($metodo->getParameters() as $parametro) {
            $nombre = $parametro->getName();

            if (array_key_exists($nombre, $parameters)) {
                $aceptados[] = $parameters[$nombre];

                continue;
            }

            $tipo = $parametro->getType();
            $encontrado = false;

            if ($tipo instanceof ReflectionNamedType && ! $tipo->isBuiltin()) {
                foreach ($parameters as $valor) {
                    if (is_object($valor) && $valor instanceof ($tipo->getName())) {
                        $aceptados[] = $valor;
                        $encontrado = true;
                        break;
                    }
                }
            }

            if (! $encontrado && $parametro->isDefaultValueAvailable()) {
                $aceptados[] = $parametro->getDefaultValue();
            }
        }

        return $this->{$method}(...$aceptados);
    }
}
