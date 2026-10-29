<?php

namespace Tests\Unit;

use App\Services\PlanPagos;
use PHPUnit\Framework\TestCase;

class PlanPagosTest extends TestCase
{
    public function test_cien_mil_al_veinte_en_cinco_semanas(): void
    {
        $plan = PlanPagos::simular(100000, 20, 5, 'semanal', '2026-11-02');

        $this->assertSame(20000, $plan['interes']);
        $this->assertSame(120000, $plan['total']);
        $this->assertSame(24000, $plan['valor_cuota']);
        $this->assertSame(
            ['2026-11-02', '2026-11-09', '2026-11-16', '2026-11-23', '2026-11-30'],
            array_column($plan['filas'], 'fecha')
        );
        $this->assertSame(0, $plan['filas'][4]['saldo']);
    }

    public function test_mensual_no_se_pasa_del_ultimo_dia(): void
    {
        $plan = PlanPagos::simular(100000, 10, 2, 'mensual', '2026-01-31');

        $this->assertSame(55000, $plan['valor_cuota']);
        $this->assertSame(['2026-01-31', '2026-02-28'], array_column($plan['filas'], 'fecha'));
    }

    public function test_el_residuo_de_pesos_cae_en_la_ultima_cuota(): void
    {
        $plan = PlanPagos::simular(100, 0, 3, 'diario', '2026-11-02');

        $this->assertSame([33, 33, 34], array_column($plan['filas'], 'valor'));
        $this->assertSame(['2026-11-02', '2026-11-03', '2026-11-04'], array_column($plan['filas'], 'fecha'));
    }
}
