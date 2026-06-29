<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ClienteController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $q = trim((string) $request->input('q', ''));

        $clientes = Cliente::query()
            ->withCount('pedidos')
            ->when($q !== '', fn ($query) => $query->where('nombre', 'like', "%{$q}%")
                ->orWhere('telefono', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%"))
            ->orderBy('nombre')
            ->get()
            ->map(fn (Cliente $c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'telefono' => $c->telefono,
                'email' => $c->email,
                'ciudad' => $c->ciudad,
                'pedidos_count' => $c->pedidos_count,
            ]);

        return Inertia::render('Clientes/Index', [
            'clientes' => $clientes,
            'filtros' => ['q' => $q],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Clientes/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Cliente::create($this->validated($request));

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado.');
    }

    public function edit(Cliente $cliente): InertiaResponse
    {
        return Inertia::render('Clientes/Edit', [
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'telefono' => $cliente->telefono,
                'email' => $cliente->email,
                'ciudad' => $cliente->ciudad,
                'notas' => $cliente->notas,
            ],
        ]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($this->validated($request, $cliente->id));

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:30', 'unique:clientes,telefono'.($ignoreId ? ",{$ignoreId}" : '')],
            'email' => ['nullable', 'email', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string'],
        ]);
    }
}
