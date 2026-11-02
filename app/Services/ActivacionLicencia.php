<?php

namespace App\Services;

class ActivacionLicencia
{
    private const CHARSET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public const PLANES = ['basico', 'premium', 'enterprise'];

    public const DURACIONES = [30, 90, 180, 365];

    public static function codigoSolicitud(string $semilla): string
    {
        $corto = substr(hash('sha256', $semilla.'REQUEST'), 0, 16);
        $codigo = '';
        $largo = strlen(self::CHARSET);

        for ($i = 0; $i < 16; $i++) {
            $codigo .= self::CHARSET[hexdec($corto[$i]) % $largo];
        }

        return implode('-', str_split($codigo, 4));
    }

    public static function generar(string $solicitud, string $plan = 'basico', int $dias = 365): string
    {
        return self::codigo($solicitud, $plan, $dias, self::secreto());
    }

    /**
     * @return array{plan: string, days: int}|null
     */
    public static function resolver(string $activacion, string $solicitud): ?array
    {
        $recibido = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $activacion));
        $secretos = array_values(array_filter(
            [self::leer('COBROS_LICENCIA_SECRETO', 'cobros.licencia_secreto'), self::leer('COBROS_LICENCIA_SECRETO_ANTERIOR', 'cobros.licencia_secreto_anterior')],
            fn (string $secreto) => strlen($secreto) >= 24
        ));

        foreach ($secretos as $secreto) {
            foreach (self::PLANES as $plan) {
                foreach (self::DURACIONES as $dias) {
                    $esperado = preg_replace('/[^A-Z0-9]/i', '', self::codigo($solicitud, $plan, $dias, $secreto));
                    if (hash_equals($esperado, $recibido)) {
                        return ['plan' => $plan, 'days' => $dias];
                    }
                }
            }

            $legado = preg_replace('/[^A-Z0-9]/i', '', self::codigoLegado($solicitud, $secreto));
            if (hash_equals($legado, $recibido)) {
                return ['plan' => 'basico', 'days' => 365];
            }
        }

        return null;
    }

    private static function codigo(string $solicitud, string $plan, int $dias, string $secreto): string
    {
        $limpia = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $solicitud));
        $plan = in_array($plan, self::PLANES, true) ? $plan : 'basico';
        $dias = in_array($dias, self::DURACIONES, true) ? $dias : 365;
        $hash = hash('sha512', $limpia.$secreto.date('Y').strtoupper($plan).$dias);
        $codigo = '';
        $largo = strlen(self::CHARSET);

        for ($i = 0; $i < 24; $i++) {
            $codigo .= self::CHARSET[hexdec(substr($hash, $i * 2, 2)) % $largo];
        }

        return implode('-', str_split($codigo, 4));
    }

    private static function codigoLegado(string $solicitud, string $secreto): string
    {
        $limpia = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $solicitud));
        $hash = hash('sha512', $limpia.$secreto.date('Y'));
        $codigo = '';
        $largo = strlen(self::CHARSET);

        for ($i = 0; $i < 24; $i++) {
            $codigo .= self::CHARSET[hexdec(substr($hash, $i * 2, 2)) % $largo];
        }

        return implode('-', str_split($codigo, 4));
    }

    private static function secreto(): string
    {
        $secreto = self::leer('COBROS_LICENCIA_SECRETO', 'cobros.licencia_secreto');

        if (strlen($secreto) < 24) {
            throw new \RuntimeException('Falta COBROS_LICENCIA_SECRETO.');
        }

        return $secreto;
    }

    private static function leer(string $variable, string $config): string
    {
        if (function_exists('app') && function_exists('config') && app()->bound('config')) {
            return (string) config($config);
        }

        $valor = getenv($variable);

        return is_string($valor) ? $valor : '';
    }
}
