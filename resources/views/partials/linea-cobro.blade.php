<a class="linea-cobro" href="{{ cobro_route('cuadre', isset($fecha) && $fecha ? ['caja' => $item['id'], 'fecha' => $fecha] : ['caja' => $item['id']]) }}">{{ $item['linea'] }}</a>
