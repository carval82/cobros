<?php

namespace App\Http\Controllers;

use App\Models\CajaCobro;
use App\Models\Gasto;
use App\Models\Jornada;
use App\Models\User;
use App\Services\CuadreCobro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CuadreController extends Controller
{
    public function index(Request $request): View
    {
        $fecha = $this->fecha($request);
        $informes = $this->informes($request);
        $id = (int) $request->query('caja');
        $informe = $id > 0 ? $informes->firstWhere('id', $id) : null;

        return view('cuadre', [
            'fecha' => $fecha,
            'coordina' => $this->coordina($request->user()),
            'puedeAperturar' => $this->puedeAperturar($request->user()),
            'informes' => $informes,
            'informe' => $informe,
            'cobradoresEmpresa' => CajaCobro::cobradoresDeLaEmpresa(),
            'equipoIds' => [],
        ]);
    }

    public function datos(Request $request): JsonResponse
    {
        return response()->json([
            'token' => csrf_token(),
            'fecha' => $this->fecha($request),
            'coordina' => $this->coordina($request->user()),
            'puede_aperturar' => $this->puedeAperturar($request->user()),
            'cajas' => $this->informes($request)->values(),
        ]);
    }

    public function abrir(Request $request): RedirectResponse|JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['nullable', 'string', 'max:80'],
            'valor_inicial' => ['required', 'integer', 'min:1'],
            'caja_id' => ['nullable', 'integer'],
            'cobradores' => ['nullable', 'array'],
            'cobradores.*' => ['integer'],
        ], [
            'valor_inicial.required' => 'Escribe el valor inicial con el que sale el cobro.',
            'valor_inicial.min' => 'El valor inicial tiene que ser mayor que cero.',
        ]);

        $usuario = $request->user();
        $valor = (int) $datos['valor_inicial'];
        $nombre = trim((string) ($datos['nombre'] ?? '')) ?: $usuario->name;

        if (! empty($datos['caja_id'])) {
            $caja = $this->autorizada($request);
            if ($caja->jornadas()->exists()) {
                throw ValidationException::withMessages([
                    'valor_inicial' => 'Este cobro ya está aperturado.',
                ]);
            }
            $caja->update(['capital' => $valor, 'nombre' => $nombre]);
        } else {
            if (CajaCobro::query()->where('user_id', $usuario->id)->where('abierto', true)->exists()) {
                throw ValidationException::withMessages([
                    'valor_inicial' => 'Ya tienes un cobro abierto. Cierra el día y sigue con ese.',
                ]);
            }
            $admin = $usuario->es_maestro || ($usuario->rol ?: 'administrador') === 'administrador';
            $caja = CajaCobro::query()->create([
                'user_id' => $usuario->id,
                'nombre' => $nombre,
                'capital' => $valor,
                'abierto' => true,
                'tipo' => $admin ? 'administrador' : 'cobrador',
            ]);
        }

        Jornada::query()->create([
            'caja_cobro_id' => $caja->id,
            'fecha' => now()->toDateString(),
            'valor_inicial' => $valor,
        ]);
        $caja->sincronizarCobradores($datos['cobradores'] ?? []);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'token' => csrf_token(), 'id' => $caja->id, 'base' => $valor]);
        }

        return $this->volver($request, $caja, 'Cobro aperturado. La base queda en ese valor inicial y el día ya puede repartir préstamos.');
    }

    public function equipo(Request $request): RedirectResponse|JsonResponse
    {
        $caja = $this->autorizada($request);
        $datos = $request->validate([
            'cobradores' => ['nullable', 'array'],
            'cobradores.*' => ['integer'],
        ]);
        $caja->sincronizarCobradores($datos['cobradores'] ?? []);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'token' => csrf_token()]);
        }

        return $this->volver($request, $caja, 'Este cobro queda con esos cobradores. Sin ellos, lo desarrolla el administrador.');
    }

    public function dia(Request $request): RedirectResponse|JsonResponse
    {
        $caja = $this->autorizada($request);
        $abierta = $caja->jornadas()->where('cerrada', false)->orderBy('fecha')->first();
        if ($abierta) {
            throw ValidationException::withMessages([
                'caja_id' => 'Cierra el día '.$abierta->fecha->format('d/m/Y').' antes de abrir el siguiente.',
            ]);
        }
        if (! $caja->jornadas()->exists()) {
            throw ValidationException::withMessages([
                'caja_id' => 'Apertura el cobro con un valor inicial.',
            ]);
        }
        if ($caja->jornadas()->whereDate('fecha', today())->exists()) {
            throw ValidationException::withMessages([
                'caja_id' => 'El día de hoy ya está abierto o ya quedó cerrado.',
            ]);
        }

        $efectivo = CuadreCobro::efectivo($caja);
        Jornada::query()->create([
            'caja_cobro_id' => $caja->id,
            'fecha' => now()->toDateString(),
            'valor_inicial' => $efectivo,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'token' => csrf_token(), 'valor_inicial' => $efectivo]);
        }

        return $this->volver($request, $caja, 'Día abierto. Sales con el valor que dejó el cierre anterior.');
    }

    public function cerrar(Request $request): RedirectResponse|JsonResponse
    {
        $caja = $this->autorizada($request);
        $datos = $request->validate([
            'fecha' => ['required', 'date'],
            'decision' => ['required', 'in:retirar,sumar'],
        ]);
        $jornada = $this->jornadaAbierta($caja, $datos['fecha']);
        $jornada = CuadreCobro::cerrar($caja, $jornada, $datos['decision']);
        $caja->refresh();

        $texto = $datos['decision'] === 'retirar'
            ? 'Día cerrado. La ganancia sale y la base se sostiene. El siguiente día abre con ese efectivo.'
            : 'Día cerrado. La ganancia se sumó a la base y el efectivo queda para el día siguiente.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'token' => csrf_token(),
                'valor_siguiente' => (int) $jornada->valor_siguiente,
                'base' => (int) $caja->capital,
                'retiro' => (int) $jornada->retiro,
            ]);
        }

        return $this->volver($request, $caja, $texto);
    }

    public function refuerzo(Request $request): RedirectResponse|JsonResponse
    {
        $caja = $this->autorizada($request);
        $datos = $request->validate([
            'monto' => ['required', 'integer', 'min:1'],
            'fecha' => ['required', 'date'],
        ], [
            'monto.required' => 'Escribe el dinero que sumas a la base.',
        ]);
        $jornada = $this->jornadaAbierta($caja, $datos['fecha']);
        $jornada->increment('refuerzo', (int) $datos['monto']);
        $caja->increment('capital', (int) $datos['monto']);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'token' => csrf_token(), 'base' => (int) $caja->fresh()->capital]);
        }

        return $this->volver($request, $caja, 'Ese dinero quedó sumado a la base y entra en el efectivo del día.');
    }

    public function gasto(Request $request): RedirectResponse|JsonResponse
    {
        $caja = $this->autorizada($request);
        $datos = $request->validate([
            'monto' => ['required', 'integer', 'min:1'],
            'fecha' => ['required', 'date'],
            'nota' => ['nullable', 'string', 'max:180'],
        ], [
            'monto.required' => 'Escribe el valor del gasto.',
        ]);
        $this->jornadaAbierta($caja, $datos['fecha']);

        Gasto::query()->create([
            'caja_cobro_id' => $caja->id,
            'user_id' => $request->user()->id,
            'monto' => (int) $datos['monto'],
            'fecha' => $datos['fecha'],
            'nota' => $datos['nota'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'token' => csrf_token()]);
        }

        return $this->volver($request, $caja, 'Gasto registrado en el cobro.');
    }

    private function informes(Request $request)
    {
        $usuario = $request->user();
        $fecha = $this->fecha($request);

        return CajaCobro::visiblesPara($usuario)->map(function (CajaCobro $caja) use ($usuario, $fecha) {
            return CuadreCobro::armar($caja, $fecha) + [
                'edita' => $usuario->puede('cobros.registrar')
                    && ($this->coordina($usuario) || (int) $caja->user_id === (int) $usuario->id),
            ];
        });
    }

    private function jornadaAbierta(CajaCobro $caja, string $fecha): Jornada
    {
        $jornada = $caja->jornadas()->whereDate('fecha', $fecha)->first();
        if (! $jornada || $jornada->cerrada) {
            throw ValidationException::withMessages([
                'fecha' => 'Ese día no está abierto. Apertura el cobro o abre el día antes de mover la caja.',
            ]);
        }

        return $jornada;
    }

    private function autorizada(Request $request): CajaCobro
    {
        $caja = CajaCobro::query()->findOrFail($request->integer('caja_id'));
        $usuario = $request->user();
        abort_unless($this->coordina($usuario) || (int) $caja->user_id === (int) $usuario->id, 403);

        return $caja;
    }

    private function coordina(User $usuario): bool
    {
        return $usuario->es_maestro || $usuario->puede('usuarios.gestionar');
    }

    private function volver(Request $request, CajaCobro $caja, string $texto): RedirectResponse
    {
        if ($request->input('seguir') === 'hoy') {
            return redirect()->to(cobro_route('dashboard', ['cobro' => $caja->id]))->with('ok', $texto);
        }

        return back()->with('ok', $texto);
    }

    private function puedeAperturar(User $usuario): bool
    {
        return $usuario->puede('cobros.registrar')
            && ! CajaCobro::query()->where('user_id', $usuario->id)->where('abierto', true)->exists();
    }

    private function fecha(Request $request): string
    {
        $datos = $request->validate([
            'fecha' => ['nullable', 'date'],
        ]);

        return $datos['fecha'] ?? now()->toDateString();
    }
}
