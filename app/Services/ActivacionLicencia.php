<?php

namespace App\Services;

class ActivacionLicencia
{
    private const SECRET_KEY = 'LC_D3S1GN_S3CR3T_K3Y_2024_PR0T3CT10N';

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
        $limpia = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $solicitud));
        $plan = in_array($plan, self::PLANES, true) ? $plan : 'basico';
        $dias = in_array($dias, self::DURACIONES, true) ? $dias : 365;
        $semilla = $limpia.self::SECRET_KEY.date('Y').strtoupper($plan).$dias;
        $hash = hash('sha512', $semilla);
        $codigo = '';
        $largo = strlen(self::CHARSET);

        for ($i = 0; $i < 24; $i++) {
            $codigo .= self::CHARSET[hexdec(substr($hash, $i * 2, 2)) % $largo];
        }

        return implode('-', str_split($codigo, 4));
    }

    /**
     * @return array{plan: string, days: int}|null
     */
    public static function resolver(string $activacion, string $solicitud): ?array
    {
        $recibido = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $activacion));

        foreach (self::PLANES as $plan) {
            foreach (self::DURACIONES as $dias) {
                $esperado = preg_replace('/[^A-Z0-9]/i', '', self::generar($solicitud, $plan, $dias));
                if (hash_equals($esperado, $recibido)) {
                    return ['plan' => $plan, 'days' => $dias];
                }
            }
        }

        $legado = preg_replace('/[^A-Z0-9]/i', '', self::generarLegado($solicitud));
        if (hash_equals($legado, $recibido)) {
            return ['plan' => 'basico', 'days' => 365];
        }

        return null;
    }

    private static function generarLegado(string $solicitud): string
    {
        $limpia = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $solicitud));
        $hash = hash('sha512', $limpia.self::SECRET_KEY.date('Y'));
        $codigo = '';
        $largo = strlen(self::CHARSET);

        for ($i = 0; $i < 24; $i++) {
            $codigo .= self::CHARSET[hexdec(substr($hash, $i * 2, 2)) % $largo];
        }

        return implode('-', str_split($codigo, 4));
    }
}
