<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Pago;

class RutaCobro
{
    /**
     * @return array{
     *     paradas: list<array<string, mixed>>,
     *     centro: ?array{lat: float, lng: float, zoom: int},
     *     mapas: ?string,
     *     resumen: array{paradas: int, en_mapa: int, sin_gps: int, saldo: int}
     * }
     */
    public function armar(?float $origenLat = null, ?float $origenLng = null): array
    {
        $cuotas = Cuota::query()
            ->with(['prestamo.cliente'])
            ->where('saldo', '>', 0)
            ->whereDate('fecha_vencimiento', '<=', today())
            ->whereHas('prestamo', fn ($q) => $q->where('estado', 'activo'))
            ->get();

        $paradas = [];

        foreach ($cuotas->groupBy(fn (Cuota $cuota) => $cuota->prestamo->cliente_id) as $grupo) {
            /** @var Cuota $primera */
            $primera = $grupo->first();
            $cliente = $primera->prestamo->cliente;
            [$lat, $lng] = $this->coords($cliente);
            $dias = (int) $grupo->max(fn (Cuota $cuota) => $cuota->diasAtraso());

            $paradas[] = [
                'cliente_id' => $cliente->id,
                'prestamo_id' => $primera->prestamo_id,
                'nombre' => $cliente->nombre,
                'direccion' => $cliente->direccion,
                'telefono' => $cliente->telefono,
                'saldo' => (int) $grupo->sum('saldo'),
                'cuotas' => $grupo->count(),
                'atraso' => $dias > 0,
                'prioridad' => $dias,
                'latitud' => $lat,
                'longitud' => $lng,
                'tiene_coordenadas' => $lat !== null && $lng !== null,
            ];
        }

        $paradas = $this->ordenar($paradas, $origenLat, $origenLng);
        $conMapa = array_values(array_filter($paradas, fn (array $p) => $p['tiene_coordenadas']));

        return [
            'paradas' => $paradas,
            'centro' => $this->centro($conMapa),
            'mapas' => $this->enlaceMapas($conMapa, $origenLat, $origenLng),
            'resumen' => [
                'paradas' => count($paradas),
                'en_mapa' => count($conMapa),
                'sin_gps' => count($paradas) - count($conMapa),
                'saldo' => (int) array_sum(array_column($paradas, 'saldo')),
            ],
        ];
    }

    /** @return array{0: ?float, 1: ?float} */
    private function coords(Cliente $cliente): array
    {
        if ($this->validas($cliente->latitud, $cliente->longitud)) {
            return [(float) $cliente->latitud, (float) $cliente->longitud];
        }

        $pago = Pago::query()
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->latest('id')
            ->first();

        if ($pago && $this->validas($pago->latitud, $pago->longitud)) {
            return [(float) $pago->latitud, (float) $pago->longitud];
        }

        return [null, null];
    }

    private function validas(mixed $lat, mixed $lng): bool
    {
        if ($lat === null || $lng === null) {
            return false;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
            return false;
        }

        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    /**
     * @param  list<array<string, mixed>>  $paradas
     * @return list<array<string, mixed>>
     */
    private function ordenar(array $paradas, ?float $origenLat, ?float $origenLng): array
    {
        $con = [];
        $sin = [];

        foreach ($paradas as $parada) {
            if ($parada['tiene_coordenadas']) {
                $con[] = $parada;
            } else {
                $sin[] = $parada;
            }
        }

        $ordenadas = [];
        $actualLat = $origenLat;
        $actualLng = $origenLng;
        $tieneOrigen = $this->validas($origenLat, $origenLng);

        if (! $tieneOrigen && $con !== []) {
            usort($con, fn ($a, $b) => $b['prioridad'] <=> $a['prioridad']);
            $primera = array_shift($con);
            $ordenadas[] = $primera;
            $actualLat = $primera['latitud'];
            $actualLng = $primera['longitud'];
        }

        while ($con !== []) {
            $mejor = 0;
            $mejorDist = PHP_FLOAT_MAX;

            foreach ($con as $i => $candidato) {
                $distancia = $this->distanciaKm($actualLat, $actualLng, $candidato['latitud'], $candidato['longitud']);
                $puntaje = $distancia - ($candidato['prioridad'] * 0.05);
                if ($puntaje < $mejorDist) {
                    $mejorDist = $puntaje;
                    $mejor = $i;
                }
            }

            $elegida = $con[$mejor];
            array_splice($con, $mejor, 1);
            $ordenadas[] = $elegida;
            $actualLat = $elegida['latitud'];
            $actualLng = $elegida['longitud'];
        }

        $ordenadas = array_merge($ordenadas, $sin);

        foreach ($ordenadas as $i => &$parada) {
            $parada['orden'] = $i + 1;
        }
        unset($parada);

        return $ordenadas;
    }

    private function distanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radio = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $radio * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * @param  list<array<string, mixed>>  $conMapa
     * @return ?array{lat: float, lng: float, zoom: int}
     */
    private function centro(array $conMapa): ?array
    {
        if ($conMapa === []) {
            return null;
        }

        return [
            'lat' => array_sum(array_column($conMapa, 'latitud')) / count($conMapa),
            'lng' => array_sum(array_column($conMapa, 'longitud')) / count($conMapa),
            'zoom' => count($conMapa) === 1 ? 15 : 13,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $conMapa
     */
    private function enlaceMapas(array $conMapa, ?float $origenLat, ?float $origenLng): ?string
    {
        if ($conMapa === []) {
            return null;
        }

        $puntos = array_map(
            fn (array $p) => $p['latitud'].','.$p['longitud'],
            array_slice($conMapa, 0, 10)
        );

        if ($this->validas($origenLat, $origenLng)) {
            array_unshift($puntos, $origenLat.','.$origenLng);
        }

        return 'https://www.google.com/maps/dir/'.implode('/', $puntos);
    }
}
