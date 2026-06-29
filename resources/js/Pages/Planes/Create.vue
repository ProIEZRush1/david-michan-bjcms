<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    nombre: '',
    precio: '',
    descripcion: '',
    datos: '',
    minutos: 'Ilimitados',
    sms: 'Ilimitados',
    tipo: 'prepago',
    vigencia_dias: 30,
    activo: true,
    orden: 0,
});

const submit = () => form.post(route('planes.store'));
</script>

<template>
    <Head title="Nuevo plan" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Nuevo plan</h2>
        </template>

        <div class="mx-auto max-w-2xl">
            <form @submit.prevent="submit" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div>
                    <InputLabel for="nombre" value="Nombre del plan" />
                    <TextInput id="nombre" v-model="form.nombre" class="mt-1 block w-full" required autofocus placeholder="Ej. Plus" />
                    <InputError class="mt-2" :message="form.errors.nombre" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="precio" value="Mensualidad (MXN)" />
                        <TextInput id="precio" v-model="form.precio" type="number" step="0.01" min="0" class="mt-1 block w-full" required placeholder="249" />
                        <InputError class="mt-2" :message="form.errors.precio" />
                    </div>
                    <div>
                        <InputLabel for="tipo" value="Tipo" />
                        <select id="tipo" v-model="form.tipo" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                            <option value="prepago">Prepago</option>
                            <option value="pospago">Pospago</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.tipo" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div>
                        <InputLabel for="datos" value="Datos" />
                        <TextInput id="datos" v-model="form.datos" class="mt-1 block w-full" placeholder="15 GB" />
                    </div>
                    <div>
                        <InputLabel for="minutos" value="Minutos" />
                        <TextInput id="minutos" v-model="form.minutos" class="mt-1 block w-full" placeholder="Ilimitados" />
                    </div>
                    <div>
                        <InputLabel for="sms" value="SMS" />
                        <TextInput id="sms" v-model="form.sms" class="mt-1 block w-full" placeholder="Ilimitados" />
                    </div>
                </div>

                <div>
                    <InputLabel for="descripcion" value="Descripción" />
                    <textarea id="descripcion" v-model="form.descripcion" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" placeholder="¿Para quién es ideal este plan?"></textarea>
                    <InputError class="mt-2" :message="form.errors.descripcion" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="vigencia_dias" value="Vigencia (días)" />
                        <TextInput id="vigencia_dias" v-model="form.vigencia_dias" type="number" min="1" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.vigencia_dias" />
                    </div>
                    <div>
                        <InputLabel for="orden" value="Orden" />
                        <TextInput id="orden" v-model="form.orden" type="number" class="mt-1 block w-full" />
                    </div>
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" v-model="form.activo" class="rounded border-slate-300 text-[#7c3aed] focus:ring-[#7c3aed]" />
                    <span class="text-sm text-slate-600">Plan activo (visible para el bot)</span>
                </label>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link :href="route('planes.index')" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700">
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90 disabled:opacity-50"
                    >
                        Guardar plan
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
