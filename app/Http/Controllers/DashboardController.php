<?php

namespace App\Http\Controllers;

use App\Models\BotContact;
use App\Models\Cliente;
use App\Models\Faq;
use App\Models\Numero;
use App\Models\Pedido;
use App\Models\Plan;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    public function index(): InertiaResponse
    {
        $ingresos = Pedido::whereIn('estado', ['pagado', 'numero_asignado', 'entregado'])->sum('total');

        return Inertia::render('Dashboard', [
            'metrics' => [
                'planes' => Plan::count(),
                'planesActivos' => Plan::where('activo', true)->count(),
                'numeros' => Numero::count(),
                'numerosDisponibles' => Numero::where('estado', 'disponible')->count(),
                'pedidos' => Pedido::count(),
                'pedidosAbiertos' => Pedido::whereNotIn('estado', ['entregado', 'cancelado'])->count(),
                'clientes' => Cliente::count(),
                'faqs' => Faq::where('activo', true)->count(),
                'conversaciones' => BotContact::count(),
                'enEspera' => BotContact::where('step', 'human')->count(),
                'ingresos' => (int) $ingresos,
            ],
            'ultimosPedidos' => Pedido::with(['plan', 'numero'])
                ->latest()
                ->take(6)
                ->get()
                ->map(fn (Pedido $p) => [
                    'id' => $p->id,
                    'cliente' => $p->cliente,
                    'telefono' => $p->telefono,
                    'plan' => $p->plan?->nombre,
                    'numero' => $p->numero?->numero,
                    'total' => $p->total,
                    'estado' => $p->estado,
                    'estado_label' => $p->estado_label,
                    'creado' => $p->created_at?->format('d/m/Y H:i'),
                ]),
        ]);
    }
}
