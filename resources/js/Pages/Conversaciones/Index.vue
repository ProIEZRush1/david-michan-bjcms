<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    conversaciones: { type: Array, default: () => [] },
    filtros: { type: Object, default: () => ({}) },
    enEspera: { type: Number, default: 0 },
});

const q = ref(props.filtros.q ?? '');
const buscar = () => router.get(route('conversaciones.index'), { q: q.value }, { preserveState: true, replace: true });

const liberar = (c) => useForm({}).patch(route('conversaciones.liberar', c.id));
const escalar = (c) => useForm({}).patch(route('conversaciones.escalar', c.id));

const color = (step) =>
    ({
        new: 'bg-slate-100 text-slate-600',
        choosing: 'bg-blue-100 text-blue-700',
        capturing: 'bg-indigo-100 text-indigo-700',
        confirming: 'bg-amber-100 text-amber-700',
        done: 'bg-green-100 text-green-700',
        human: 'bg-rose-100 text-rose-700',
    })[step] ?? 'bg-slate-100 text-slate-600';
</script>

<template>
    <Head title="Conversaciones" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Conversaciones del bot</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div
                v-if="enEspera > 0"
                class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700"
            >
                🙋 Hay {{ enEspera }} conversación(es) esperando atención de un asesor humano.
            </div>

            <form @submit.prevent="buscar" class="flex w-full max-w-sm gap-2">
                <input v-model="q" type="search" placeholder="Buscar por teléfono o nombre…" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                <button type="submit" class="rounded-xl bg-slate-100 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200">Buscar</button>
            </form>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3">Contacto</th>
                            <th class="px-5 py-3">Teléfono</th>
                            <th class="px-5 py-3">Etapa</th>
                            <th class="px-5 py-3">Pedidos</th>
                            <th class="px-5 py-3">Actualizado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in conversaciones" :key="c.id" class="text-slate-700">
                            <td class="px-5 py-4 font-semibold text-slate-800">{{ c.name || 'Sin nombre' }}</td>
                            <td class="px-5 py-4">{{ c.phone }}</td>
                            <td class="px-5 py-4">
                                <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', color(c.step)]">{{ c.step_label }}</span>
                            </td>
                            <td class="px-5 py-4">{{ c.pedidos_count }}</td>
                            <td class="px-5 py-4 text-xs text-slate-400">{{ c.actualizado }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <button v-if="c.step === 'human'" @click="liberar(c)" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-green-600 hover:bg-green-50">
                                        Devolver al bot
                                    </button>
                                    <button v-else @click="escalar(c)" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-[#7c3aed] hover:bg-violet-50">
                                        Atender yo
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!conversaciones.length">
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">
                                Aún no hay conversaciones. Aparecerán aquí cuando los clientes escriban a tu WhatsApp.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
