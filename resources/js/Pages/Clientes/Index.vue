<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    clientes: { type: Array, default: () => [] },
    filtros: { type: Object, default: () => ({}) },
});

const q = ref(props.filtros.q ?? '');
const buscar = () => router.get(route('clientes.index'), { q: q.value }, { preserveState: true, replace: true });

const eliminar = (c) => {
    if (confirm(`¿Eliminar al cliente "${c.nombre}"?`)) {
        useForm({}).delete(route('clientes.destroy', c.id));
    }
};
</script>

<template>
    <Head title="Clientes" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Clientes</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form @submit.prevent="buscar" class="flex w-full max-w-sm gap-2">
                    <input v-model="q" type="search" placeholder="Buscar por nombre, teléfono o correo…" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                    <button type="submit" class="rounded-xl bg-slate-100 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200">Buscar</button>
                </form>
                <Link :href="route('clientes.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90">
                    + Nuevo cliente
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3">Teléfono</th>
                            <th class="px-5 py-3">Correo</th>
                            <th class="px-5 py-3">Ciudad</th>
                            <th class="px-5 py-3">Pedidos</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in clientes" :key="c.id" class="text-slate-700">
                            <td class="px-5 py-4 font-semibold text-slate-800">{{ c.nombre }}</td>
                            <td class="px-5 py-4">{{ c.telefono }}</td>
                            <td class="px-5 py-4">{{ c.email || '—' }}</td>
                            <td class="px-5 py-4">{{ c.ciudad || '—' }}</td>
                            <td class="px-5 py-4">{{ c.pedidos_count }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <Link :href="route('clientes.edit', c.id)" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-[#7c3aed] hover:bg-violet-50">Editar</Link>
                                    <button @click="eliminar(c)" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Eliminar</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!clientes.length">
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">Aún no hay clientes registrados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
