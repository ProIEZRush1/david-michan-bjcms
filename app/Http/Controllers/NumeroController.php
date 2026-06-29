<?php

namespace App\Http\Controllers;

use App\Models\Numero;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class NumeroController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $q = trim((string) $request->input('q', ''));
        $estado = (string) $request->input('estado', '');

        $numeros = Numero::query()
            ->with('plan')
            ->when($q !== '', fn ($query) => $query->where('numero', 'like', "%{$q}%")
                ->orWhere('lada', 'like', "%{$q}%"))
            ->when($estado !== '', fn ($query) => $query->where('estado', $estado))
            ->orderBy('estado')
            ->orderBy('numero')
            ->get()
            ->map(fn (Numero $n) => [
                'id' => $n->id,
                'numero' => $n->numero,
                'lada' => $n->lada,
                'tipo' => $n->tipo,
                'estado' => $n->estado,
                'estado_label' => $n->estado_label,
                'plan' => $n->plan?->nombre,
            ]);

        return Inertia::render('Inventario/Index', [
            'numeros' => $numeros,
            'filtros' => ['q' => $q, 'estado' => $estado],
            'estados' => Numero::ESTADOS,
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Inventario/Create', [
            'planes' => $this->planesOptions(),
            'estados' => Numero::ESTADOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Numero::create($this->validated($request));

        return redirect()->route('inventario.index')->with('success', 'Número agregado al inventario.');
    }

    public function edit(Numero $inventario): InertiaResponse
    {
        return Inertia::render('Inventario/Edit', [
            'numero' => [
                'id' => $inventario->id,
                'numero' => $inventario->numero,
                'lada' => $inventario->lada,
                'tipo' => $inventario->tipo,
                'estado' => $inventario->estado,
                'plan_id' => $inventario->plan_id,
            ],
            'planes' => $this->planesOptions(),
            'estados' => Numero::ESTADOS,
        ]);
    }

    public function update(Request $request, Numero $inventario): RedirectResponse
    {
        $inventario->update($this->validated($request, $inventario->id));

        return redirect()->route('inventario.index')->with('success', 'Número actualizado.');
    }

    public function destroy(Numero $inventario): RedirectResponse
    {
        $inventario->delete();

        return redirect()->route('inventario.index')->with('success', 'Número eliminado.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'numero' => ['required', 'string', 'max:30', 'unique:numeros,numero'.($ignoreId ? ",{$ignoreId}" : '')],
            'lada' => ['nullable', 'string', 'max:10'],
            'tipo' => ['required', 'in:movil,fijo'],
            'estado' => ['required', 'in:disponible,reservado,asignado,baja'],
            'plan_id' => ['nullable', 'exists:planes,id'],
        ]);
    }

    private function planesOptions()
    {
        return Plan::orderBy('orden')->get(['id', 'nombre']);
    }
}
