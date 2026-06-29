<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'bot_contact_id',
        'plan_id',
        'cliente_id',
        'numero_id',
        'cliente',
        'telefono',
        'email',
        'total',
        'metodo_pago',
        'referencia_pago',
        'estado',
        'notas',
    ];

    protected $casts = [
        'total' => 'int',
    ];

    /** Flujo de estados del pedido (orden cronológico). */
    public const ESTADOS = [
        'iniciado' => 'Iniciado',
        'en_pago' => 'En pago',
        'pagado' => 'Pagado',
        'numero_asignado' => 'Número asignado',
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function botContact(): BelongsTo
    {
        return $this->belongsTo(BotContact::class);
    }

    public function clienteRel(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function numero(): BelongsTo
    {
        return $this->belongsTo(Numero::class);
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }
}
