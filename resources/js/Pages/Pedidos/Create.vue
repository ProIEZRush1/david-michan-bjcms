<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    planes: { type: Array, default: () => [] },
    estados: { type: Object, default: () => ({}) },
});

const form = useForm({
    plan_id: props.planes[0]?.id ?? '',
    cliente: '',
    telefono: '',
    email: '',
    estado: 'iniciado',
    notas: '',
});

const money = (cents) => '$' + ((cents ?? 0) / 100).toLocaleString('es-MX');
const planSel = computed(() => props.planes.find((p) => p.id === form.plan_id));

const submit = () => form.post(route('pedidos.store'));
</script>

<template>
    <Head title="Nuevo pedido" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Nuevo pedido</h2>
        </template>

        <div class="mx-auto max-w-2xl">
            <form @submit.prevent="submit" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div>
                    <InputLabel for="plan_id" value="Plan" />
                    <select id="plan_id" v-model="form.plan_id" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                        <option v-for="p in planes" :key="p.id" :value="p.id">{{ p.nombre }} — {{ money(p.precio) }}</option>
                    </select>
                    <p v-if="planSel" class="mt-1 text-xs text-slate-400">Total del pedido: {{ money(planSel.precio) }}</p>
                    <InputError class="mt-2" :message="form.errors.plan_id" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="cliente" value="Nombre del cliente" />
                        <TextInput id="cliente" v-model="form.cliente" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.cliente" />
                    </div>
                    <div>
                        <InputLabel for="telefono" value="Teléfono (WhatsApp)" />
                        <TextInput id="telefono" v-model="form.telefono" class="mt-1 block w-full" required placeholder="5215512345678" />
                        <InputError class="mt-2" :message="form.errors.telefono" />
                    </div>
                </div>

                <div>
                    <InputLabel for="email" value="Correo electrónico" />
                    <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="estado" value="Estado" />
                    <select id="estado" v-model="form.estado" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                        <option v-for="(label, key) in estados" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Al marcar “Pagado” se asigna automáticamente una línea disponible del inventario.</p>
                    <InputError class="mt-2" :message="form.errors.estado" />
                </div>

                <div>
                    <InputLabel for="notas" value="Notas" />
                    <textarea id="notas" v-model="form.notas" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link :href="route('pedidos.index')" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700">Cancelar</Link>
                    <button type="submit" :disabled="form.processing" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90 disabled:opacity-50">
                        Guardar pedido
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
