<?php

namespace Tests\Unit;

use App\Services\ActivacionLicencia;
use PHPUnit\Framework\TestCase;

class ActivacionLicenciaTest extends TestCase
{
    public function test_el_codigo_del_activador_abre_la_licencia(): void
    {
        $solicitud = 'ABCD-EFGH-JKLM-NPQR';
        $codigo = ActivacionLicencia::generar($solicitud, 'premium', 90);

        $this->assertSame(
            ['plan' => 'premium', 'days' => 90],
            ActivacionLicencia::resolver($codigo, $solicitud)
        );
        $this->assertNull(ActivacionLicencia::resolver('XXXX-XXXX-XXXX-XXXX-XXXX-XXXX', $solicitud));
    }
}
