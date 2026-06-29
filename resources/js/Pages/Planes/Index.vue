<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    planes: { type: Array, default: () => [] },
    filtros: { type: Object, default: () => ({}) },
});

const q = ref(props.filtros.q ?? '');
const money = (cents) => '$' + ((cents ?? 0) / 100).toLocaleString('es-MX');

const buscar = () => router.get(route('planes.index'), { q: q.value }, { preserveState: true, replace: true });

const eliminar = (plan) => {
    if (confirm(`¿Eliminar el plan "${plan.nombre}"?`)) {
        useForm({}).delete(route('planes.destroy', plan.id));
    }
};
</script>

<template>
    <Head title="Planes" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Planes de telefonía</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form @submit.prevent="buscar" class="flex w-full max-w-sm gap-2">
                    <input
                        v-model="q"
                        type="search"
                        placeholder="Buscar plan…"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]"
                    />
                    <button type="submit" class="rounded-xl bg-slate-100 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                        Buscar
                    </button>
                </form>
                <Link
                    :href="route('planes.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90"
                >
                    + Nuevo plan
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3">Plan</th>
                            <th class="px-5 py-3">Incluye</th>
                            <th class="px-5 py-3">Tipo</th>
                            <th class="px-5 py-3">Mensualidad</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in planes" :key="p.id" class="text-slate-700">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800">{{ p.nombre }}</p>
                                <p class="text-xs text-slate-400">{{ p.pedidos_count }} pedido(s)</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500">
                                <span v-if="p.datos">📶 {{ p.datos }}</span>
                                <span v-if="p.minutos"> · {{ p.minutos }} min</span>
                                <span v-if="p.sms"> · {{ p.sms }} SMS</span>
                            </td>
                            <td class="px-5 py-4 capitalize">{{ p.tipo }}</td>
                            <td class="px-5 py-4 font-semibold">{{ money(p.precio) }}</td>
                            <td class="px-5 py-4">
                                <span
                                    :class="p.activo ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'"
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                >
                                    {{ p.activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <Link :href="route('planes.edit', p.id)" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-[#7c3aed] hover:bg-violet-50">
                                        Editar
                                    </Link>
                                    <button @click="eliminar(p)" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!planes.length">
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">
                                No hay planes todavía. Crea el primero con “Nuevo plan”.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
