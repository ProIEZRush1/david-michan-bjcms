<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    faqs: { type: Array, default: () => [] },
    filtros: { type: Object, default: () => ({}) },
});

const q = ref(props.filtros.q ?? '');
const buscar = () => router.get(route('faqs.index'), { q: q.value }, { preserveState: true, replace: true });

const eliminar = (f) => {
    if (confirm(`¿Eliminar la pregunta "${f.pregunta}"?`)) {
        useForm({}).delete(route('faqs.destroy', f.id));
    }
};
</script>

<template>
    <Head title="Preguntas frecuentes" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Preguntas frecuentes</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form @submit.prevent="buscar" class="flex w-full max-w-sm gap-2">
                    <input v-model="q" type="search" placeholder="Buscar pregunta…" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                    <button type="submit" class="rounded-xl bg-slate-100 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200">Buscar</button>
                </form>
                <Link :href="route('faqs.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90">
                    + Nueva pregunta
                </Link>
            </div>

            <p class="text-sm text-slate-500">
                Estas respuestas alimentan a tu bot: cuando un cliente escribe algo que coincide con las
                <span class="font-semibold">palabras clave</span>, el bot responde automáticamente.
            </p>

            <div class="space-y-4">
                <div v-for="f in faqs" :key="f.id" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-slate-800">{{ f.pregunta }}</h3>
                                <span :class="f.activo ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold">
                                    {{ f.activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ f.respuesta }}</p>
                            <p v-if="f.palabras_clave" class="mt-3 text-xs text-slate-400">
                                🔑 {{ f.palabras_clave }}
                            </p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <Link :href="route('faqs.edit', f.id)" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-[#7c3aed] hover:bg-violet-50">Editar</Link>
                            <button @click="eliminar(f)" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Eliminar</button>
                        </div>
                    </div>
                </div>
                <div v-if="!faqs.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-400">
                    Aún no hay preguntas frecuentes. Crea la primera con “Nueva pregunta”.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
