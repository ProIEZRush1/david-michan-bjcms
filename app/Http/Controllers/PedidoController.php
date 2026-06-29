<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Numero;
use App\Models\Pedido;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PedidoController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $q = trim((string) $request->input('q', ''));
        $estado = (string) $request->input('estado', '');

        $pedidos = Pedido::query()
            ->with(['plan', 'numero'])
            ->when($q !== '', fn ($query) => $query->where('cliente', 'like', "%{$q}%")
                ->orWhere('telefono', 'like', "%{$q}%"))
            ->when($estado !== '', fn ($query) => $query->where('estado', $estado))
            ->latest()
            ->get()
            ->map(fn (Pedido $p) => $this->present($p));

        return Inertia::render('Pedidos/Index', [
            'pedidos' => $pedidos,
            'filtros' => ['q' => $q, 'estado' => $estado],
            'estados' => Pedido::ESTADOS,
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Pedidos/Create', [
            'planes' => Plan::orderBy('orden')->get(['id', 'nombre', 'precio']),
            'estados' => Pedido::ESTADOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:planes,id'],
            'cliente' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'estado' => ['required', 'in:'.implode(',', array_keys(Pedido::ESTADOS))],
            'notas' => ['nullable', 'string'],
        ]);

        $plan = Plan::find($data['plan_id']);
        $data['total'] = $plan?->precio ?? 0;

        // Vincula (o crea) el cliente del catálogo por su teléfono.
        $cliente = Cliente::firstOrCreate(
            ['telefono' => $data['telefono']],
            ['nombre' => $data['cliente'], 'email' => $data['email'] ?? null],
        );
        $data['cliente_id'] = $cliente->id;

        $pedido = Pedido::create($data);
        $this->syncEstado($pedido, $data['estado']);

        return redirect()->route('pedidos.index')->with('success', 'Pedido creado.');
    }

    public function edit(Pedido $pedido): InertiaResponse
    {
        $pedido->load(['plan', 'numero']);

        return Inertia::render('Pedidos/Edit', [
            'pedido' => array_merge($this->present($pedido), [
                'plan_id' => $pedido->plan_id,
                'email' => $pedido->email,
                'notas' => $pedido->notas,
                'metodo_pago' => $pedido->metodo_pago,
                'referencia_pago' => $pedido->referencia_pago,
            ]),
            'planes' => Plan::orderBy('orden')->get(['id', 'nombre', 'precio']),
            'estados' => Pedido::ESTADOS,
            'numerosDisponibles' => Numero::where('estado', 'disponible')->count(),
        ]);
    }

    public function update(Request $request, Pedido $pedido): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:planes,id'],
            'cliente' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'estado' => ['required', 'in:'.implode(',', array_keys(Pedido::ESTADOS))],
            'notas' => ['nullable', 'string'],
        ]);

        $plan = Plan::find($data['plan_id']);
        $data['total'] = $plan?->precio ?? $pedido->total;

        $pedido->update($data);
        $this->syncEstado($pedido, $data['estado']);

        return redirect()->route('pedidos.index')->with('success', 'Pedido actualizado.');
    }

    public function destroy(Pedido $pedido): RedirectResponse
    {
        // Libera el número reservado/asignado al cancelar el pedido.
        if ($pedido->numero) {
            $pedido->numero->update(['estado' => 'disponible']);
        }
        $pedido->delete();

        return redirect()->route('pedidos.index')->with('success', 'Pedido eliminado.');
    }

    /**
     * Asignación AUTOMÁTICA de número al confirmarse el pago: al pasar a 'pagado',
     * 'numero_asignado' o 'entregado' sin número, toma uno disponible del inventario.
     */
    private function syncEstado(Pedido $pedido, string $estado): void
    {
        $requiereNumero = in_array($estado, ['pagado', 'numero_asignado', 'entregado'], true);

        if ($requiereNumero && ! $pedido->numero_id) {
            $numero = Numero::whereIn('estado', ['disponible', 'reservado'])
                ->orderByRaw("CASE WHEN estado = 'reservado' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->first();

            if ($numero) {
                $numero->update(['estado' => 'asignado', 'plan_id' => $numero->plan_id ?: $pedido->plan_id]);
                $pedido->numero_id = $numero->id;
                // Al quedar pagado y con número, el estado natural es "numero_asignado".
                if ($estado === 'pagado') {
                    $pedido->estado = 'numero_asignado';
                }
                $pedido->save();
            }
        }

        // Si ya tiene número y el pago se confirma, marca el número como asignado.
        if ($requiereNumero && $pedido->numero && $pedido->numero->estado !== 'asignado') {
            $pedido->numero->update(['estado' => 'asignado']);
        }
    }

    private function present(Pedido $p): array
    {
        return [
            'id' => $p->id,
            'cliente' => $p->cliente,
            'telefono' => $p->telefono,
            'plan' => $p->plan?->nombre,
            'numero' => $p->numero?->numero,
            'total' => $p->total,
            'estado' => $p->estado,
            'estado_label' => $p->estado_label,
            'creado' => $p->created_at?->format('d/m/Y H:i'),
        ];
    }
}
