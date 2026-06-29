<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    numeros: { type: Array, default: () => [] },
    filtros: { type: Object, default: () => ({}) },
    estados: { type: Object, default: () => ({}) },
});

const q = ref(props.filtros.q ?? '');
const estado = ref(props.filtros.estado ?? '');

const buscar = () =>
    router.get(route('inventario.index'), { q: q.value, estado: estado.value }, { preserveState: true, replace: true });

const eliminar = (n) => {
    if (confirm(`¿Eliminar el número "${n.numero}"?`)) {
        useForm({}).delete(route('inventario.destroy', n.id));
    }
};

const color = (e) =>
    ({
        disponible: 'bg-green-100 text-green-700',
        reservado: 'bg-amber-100 text-amber-700',
        asignado: 'bg-violet-100 text-violet-700',
        baja: 'bg-slate-100 text-slate-500',
    })[e] ?? 'bg-slate-100 text-slate-500';
</script>

<template>
    <Head title="Inventario de números" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Inventario de números</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form @submit.prevent="buscar" class="flex w-full max-w-lg gap-2">
                    <input v-model="q" type="search" placeholder="Buscar número o lada…" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                    <select v-model="estado" @change="buscar" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                        <option value="">Todos</option>
                        <option v-for="(label, key) in estados" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <button type="submit" class="rounded-xl bg-slate-100 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200">Buscar</button>
                </form>
                <Link :href="route('inventario.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90">
                    + Nuevo número
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3">Número</th>
                            <th class="px-5 py-3">Lada</th>
                            <th class="px-5 py-3">Tipo</th>
                            <th class="px-5 py-3">Plan</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="n in numeros" :key="n.id" class="text-slate-700">
                            <td class="px-5 py-4 font-semibold text-slate-800">{{ n.numero }}</td>
                            <td class="px-5 py-4">{{ n.lada || '—' }}</td>
                            <td class="px-5 py-4 capitalize">{{ n.tipo }}</td>
                            <td class="px-5 py-4">{{ n.plan || '—' }}</td>
                            <td class="px-5 py-4">
                                <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', color(n.estado)]">{{ n.estado_label }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <Link :href="route('inventario.edit', n.id)" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-[#7c3aed] hover:bg-violet-50">Editar</Link>
                                    <button @click="eliminar(n)" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Eliminar</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!numeros.length">
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">No hay números en el inventario.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
