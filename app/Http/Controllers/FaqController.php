<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FaqController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $q = trim((string) $request->input('q', ''));

        $faqs = Faq::query()
            ->when($q !== '', fn ($query) => $query->where('pregunta', 'like', "%{$q}%")
                ->orWhere('respuesta', 'like', "%{$q}%")
                ->orWhere('palabras_clave', 'like', "%{$q}%"))
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(fn (Faq $f) => [
                'id' => $f->id,
                'pregunta' => $f->pregunta,
                'respuesta' => $f->respuesta,
                'palabras_clave' => $f->palabras_clave,
                'activo' => $f->activo,
                'orden' => $f->orden,
            ]);

        return Inertia::render('Faqs/Index', [
            'faqs' => $faqs,
            'filtros' => ['q' => $q],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Faqs/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Faq::create($this->validated($request));

        return redirect()->route('faqs.index')->with('success', 'Pregunta frecuente creada.');
    }

    public function edit(Faq $faq): InertiaResponse
    {
        return Inertia::render('Faqs/Edit', [
            'faq' => [
                'id' => $faq->id,
                'pregunta' => $faq->pregunta,
                'respuesta' => $faq->respuesta,
                'palabras_clave' => $faq->palabras_clave,
                'activo' => $faq->activo,
                'orden' => $faq->orden,
            ],
        ]);
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $faq->update($this->validated($request));

        return redirect()->route('faqs.index')->with('success', 'Pregunta frecuente actualizada.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('faqs.index')->with('success', 'Pregunta frecuente eliminada.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'pregunta' => ['required', 'string', 'max:255'],
            'respuesta' => ['required', 'string'],
            'palabras_clave' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
            'orden' => ['nullable', 'integer'],
        ]);
    }
}
