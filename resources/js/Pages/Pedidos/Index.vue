<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pedidos: { type: Array, default: () => [] },
    filtros: { type: Object, default: () => ({}) },
    estados: { type: Object, default: () => ({}) },
});

const q = ref(props.filtros.q ?? '');
const estado = ref(props.filtros.estado ?? '');
const money = (cents) => '$' + ((cents ?? 0) / 100).toLocaleString('es-MX');

const buscar = () =>
    router.get(route('pedidos.index'), { q: q.value, estado: estado.value }, { preserveState: true, replace: true });

const eliminar = (p) => {
    if (confirm(`¿Eliminar el pedido #${p.id}?`)) {
        useForm({}).delete(route('pedidos.destroy', p.id));
    }
};

const color = (e) =>
    ({
        iniciado: 'bg-slate-100 text-slate-600',
        en_pago: 'bg-amber-100 text-amber-700',
        pagado: 'bg-blue-100 text-blue-700',
        numero_asignado: 'bg-violet-100 text-violet-700',
        entregado: 'bg-green-100 text-green-700',
        cancelado: 'bg-rose-100 text-rose-700',
    })[e] ?? 'bg-slate-100 text-slate-600';
</script>

<template>
    <Head title="Pedidos" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Pedidos</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form @submit.prevent="buscar" class="flex w-full max-w-lg gap-2">
                    <input v-model="q" type="search" placeholder="Buscar por cliente o teléfono…" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                    <select v-model="estado" @change="buscar" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                        <option value="">Todos</option>
                        <option v-for="(label, key) in estados" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <button type="submit" class="rounded-xl bg-slate-100 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200">Buscar</button>
                </form>
                <Link :href="route('pedidos.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90">
                    + Nuevo pedido
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3">Plan</th>
                            <th class="px-5 py-3">Línea</th>
                            <th class="px-5 py-3">Total</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in pedidos" :key="p.id" class="text-slate-700">
                            <td class="px-5 py-4 font-mono text-xs text-slate-400">#{{ p.id }}</td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800">{{ p.cliente || '—' }}</p>
                                <p class="text-xs text-slate-400">{{ p.telefono }}</p>
                            </td>
                            <td class="px-5 py-4">{{ p.plan || '—' }}</td>
                            <td class="px-5 py-4">{{ p.numero || '—' }}</td>
                            <td class="px-5 py-4 font-semibold">{{ money(p.total) }}</td>
                            <td class="px-5 py-4">
                                <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', color(p.estado)]">{{ p.estado_label }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <Link :href="route('pedidos.edit', p.id)" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-[#7c3aed] hover:bg-violet-50">Gestionar</Link>
                                    <button @click="eliminar(p)" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Eliminar</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!pedidos.length">
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400">No hay pedidos todavía.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
