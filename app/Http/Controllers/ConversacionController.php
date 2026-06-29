<?php

namespace App\Http\Controllers;

use App\Models\BotContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ConversacionController extends Controller
{
    /** Etiquetas legibles de cada paso del bot. */
    public const STEPS = [
        'new' => 'Nuevo',
        'choosing' => 'Eligiendo plan',
        'capturing' => 'Capturando datos',
        'confirming' => 'Por confirmar',
        'done' => 'Pedido registrado',
        'human' => 'Con asesor humano',
    ];

    public function index(Request $request): InertiaResponse
    {
        $q = trim((string) $request->input('q', ''));

        $conversaciones = BotContact::query()
            ->withCount('pedidos')
            ->when($q !== '', fn ($query) => $query->where('phone', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%"))
            ->orderByRaw("CASE WHEN step = 'human' THEN 0 ELSE 1 END")
            ->latest('updated_at')
            ->get()
            ->map(fn (BotContact $c) => [
                'id' => $c->id,
                'phone' => $c->phone,
                'name' => $c->name,
                'step' => $c->step,
                'step_label' => self::STEPS[$c->step] ?? $c->step,
                'pedidos_count' => $c->pedidos_count,
                'actualizado' => $c->updated_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Conversaciones/Index', [
            'conversaciones' => $conversaciones,
            'filtros' => ['q' => $q],
            'enEspera' => BotContact::where('step', 'human')->count(),
        ]);
    }

    /** Devuelve la conversación al bot tras la atención humana. */
    public function liberar(BotContact $conversacion): RedirectResponse
    {
        $conversacion->update(['step' => 'new']);

        return redirect()->route('conversaciones.index')
            ->with('success', 'Conversación devuelta al bot.');
    }

    /** Marca una conversación para atención humana (silencia el bot). */
    public function escalar(BotContact $conversacion): RedirectResponse
    {
        $conversacion->update(['step' => 'human']);

        return redirect()->route('conversaciones.index')
            ->with('success', 'Conversación asignada a un asesor.');
    }
}
