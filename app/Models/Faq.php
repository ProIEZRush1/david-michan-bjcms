<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faqs';

    protected $fillable = [
        'pregunta',
        'respuesta',
        'palabras_clave',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'bool',
        'orden' => 'int',
    ];

    /** @return array<int,string> Palabras clave normalizadas (minúsculas, sin espacios extra). */
    public function keywords(): array
    {
        return collect(explode(',', (string) $this->palabras_clave))
            ->map(fn ($k) => mb_strtolower(trim($k)))
            ->filter()
            ->values()
            ->all();
    }
}
