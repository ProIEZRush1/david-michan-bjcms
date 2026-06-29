<script setup>
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
    numero: '',
    lada: '',
    tipo: 'movil',
    estado: 'disponible',
    plan_id: '',
});

const submit = () => form.post(route('inventario.store'));
</script>

<template>
    <Head title="Nuevo número" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Nuevo número</h2>
        </template>

        <div class="mx-auto max-w-2xl">
            <form @submit.prevent="submit" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div>
                    <InputLabel for="numero" value="Número" />
                    <TextInput id="numero" v-model="form.numero" class="mt-1 block w-full" required autofocus placeholder="55 1234 5678" />
                    <InputError class="mt-2" :message="form.errors.numero" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="lada" value="Lada" />
                        <TextInput id="lada" v-model="form.lada" class="mt-1 block w-full" placeholder="55" />
                        <InputError class="mt-2" :message="form.errors.lada" />
                    </div>
                    <div>
                        <InputLabel for="tipo" value="Tipo" />
                        <select id="tipo" v-model="form.tipo" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                            <option value="movil">Móvil</option>
                            <option value="fijo">Fijo</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="estado" value="Estado" />
                        <select id="estado" v-model="form.estado" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                            <option v-for="(label, key) in estados" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.estado" />
                    </div>
                    <div>
                        <InputLabel for="plan_id" value="Plan asociado (opcional)" />
                        <select id="plan_id" v-model="form.plan_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                            <option value="">— Sin plan —</option>
                            <option v-for="p in planes" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link :href="route('inventario.index')" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700">Cancelar</Link>
                    <button type="submit" :disabled="form.processing" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90 disabled:opacity-50">
                        Guardar número
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
