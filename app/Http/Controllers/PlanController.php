<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PlanController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $q = trim((string) $request->input('q', ''));

        $planes = Plan::query()
            ->when($q !== '', fn ($query) => $query->where('nombre', 'like', "%{$q}%")
                ->orWhere('descripcion', 'like', "%{$q}%"))
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(fn (Plan $p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'precio' => $p->precio,
                'datos' => $p->datos,
                'minutos' => $p->minutos,
                'sms' => $p->sms,
                'tipo' => $p->tipo,
                'vigencia_dias' => $p->vigencia_dias,
                'activo' => $p->activo,
                'orden' => $p->orden,
                'pedidos_count' => $p->pedidos()->count(),
            ]);

        return Inertia::render('Planes/Index', [
            'planes' => $planes,
            'filtros' => ['q' => $q],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Planes/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['precio'] = (int) round(((float) $request->input('precio')) * 100);
        Plan::create($data);

        return redirect()->route('planes.index')->with('success', 'Plan creado correctamente.');
    }

    public function edit(Plan $plan): InertiaResponse
    {
        return Inertia::render('Planes/Edit', [
            'plan' => [
                'id' => $plan->id,
                'nombre' => $plan->nombre,
                'precio' => $plan->precio / 100,
                'descripcion' => $plan->descripcion,
                'datos' => $plan->datos,
                'minutos' => $plan->minutos,
                'sms' => $plan->sms,
                'tipo' => $plan->tipo,
                'vigencia_dias' => $plan->vigencia_dias,
                'activo' => $plan->activo,
                'orden' => $plan->orden,
            ],
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $this->validated($request);
        $data['precio'] = (int) round(((float) $request->input('precio')) * 100);
        $plan->update($data);

        return redirect()->route('planes.index')->with('success', 'Plan actualizado.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('planes.index')->with('success', 'Plan eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string'],
            'datos' => ['nullable', 'string', 'max:255'],
            'minutos' => ['nullable', 'string', 'max:255'],
            'sms' => ['nullable', 'string', 'max:255'],
            'tipo' => ['required', 'in:prepago,pospago'],
            'vigencia_dias' => ['required', 'integer', 'min:1'],
            'activo' => ['boolean'],
            'orden' => ['nullable', 'integer'],
        ]);
    }
}
