@php
    $mapa = [
        'activo' => ['Al día', 'ok'],
        'mora' => ['En mora', 'danger'],
        'detenido' => ['Detenido', 'pause'],
        'liquidado' => ['Liquidado', 'done'],
        'anulado' => ['Anulado', 'muted'],
        'pendiente' => ['Pendiente', 'wait'],
        'parcial' => ['Abono parcial', 'wait'],
        'pagada' => ['Pagada', 'done'],
        'vencida' => ['Atrasada', 'danger'],
        'detenida' => ['Detenida', 'pause'],
    ];
    [$texto, $tono] = $mapa[$estado] ?? [$estado, 'muted'];
@endphp
<span class="badge badge-{{ $tono }}">{{ $texto }}</span>
