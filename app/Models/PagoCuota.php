<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoCuota extends Model
{
    protected $fillable = [
        'pago_id',
        'cuota_id',
        'monto',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'integer',
        ];
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class);
    }
}
