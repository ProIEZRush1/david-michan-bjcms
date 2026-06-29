<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Numero extends Model
{
    protected $table = 'numeros';

    protected $fillable = [
        'numero',
        'lada',
        'tipo',
        'estado',
        'plan_id',
    ];

    public const ESTADOS = [
        'disponible' => 'Disponible',
        'reservado' => 'Reservado',
        'asignado' => 'Asignado',
        'baja' => 'Baja',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }
}
