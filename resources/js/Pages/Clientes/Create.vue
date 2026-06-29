<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    nombre: '',
    telefono: '',
    email: '',
    ciudad: '',
    notas: '',
});

const submit = () => form.post(route('clientes.store'));
</script>

<template>
    <Head title="Nuevo cliente" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Nuevo cliente</h2>
        </template>

        <div class="mx-auto max-w-2xl">
            <form @submit.prevent="submit" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div>
                    <InputLabel for="nombre" value="Nombre completo" />
                    <TextInput id="nombre" v-model="form.nombre" class="mt-1 block w-full" required autofocus />
                    <InputError class="mt-2" :message="form.errors.nombre" />
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="telefono" value="Teléfono (WhatsApp)" />
                        <TextInput id="telefono" v-model="form.telefono" class="mt-1 block w-full" required placeholder="5215512345678" />
                        <InputError class="mt-2" :message="form.errors.telefono" />
                    </div>
                    <div>
                        <InputLabel for="email" value="Correo electrónico" />
                        <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                </div>
                <div>
                    <InputLabel for="ciudad" value="Ciudad" />
                    <TextInput id="ciudad" v-model="form.ciudad" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel for="notas" value="Notas" />
                    <textarea id="notas" v-model="form.notas" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link :href="route('clientes.index')" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700">Cancelar</Link>
                    <button type="submit" :disabled="form.processing" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90 disabled:opacity-50">
                        Guardar cliente
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
