<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ faq: { type: Object, required: true } });

const form = useForm({
    pregunta: props.faq.pregunta ?? '',
    respuesta: props.faq.respuesta ?? '',
    palabras_clave: props.faq.palabras_clave ?? '',
    activo: !!props.faq.activo,
    orden: props.faq.orden ?? 0,
});

const submit = () => form.put(route('faqs.update', props.faq.id));
</script>

<template>
    <Head title="Editar pregunta" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Editar pregunta frecuente</h2>
        </template>

        <div class="mx-auto max-w-2xl">
            <form @submit.prevent="submit" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div>
                    <InputLabel for="pregunta" value="Pregunta" />
                    <TextInput id="pregunta" v-model="form.pregunta" class="mt-1 block w-full" required autofocus />
                    <InputError class="mt-2" :message="form.errors.pregunta" />
                </div>
                <div>
                    <InputLabel for="respuesta" value="Respuesta del bot" />
                    <textarea id="respuesta" v-model="form.respuesta" rows="4" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]"></textarea>
                    <InputError class="mt-2" :message="form.errors.respuesta" />
                </div>
                <div>
                    <InputLabel for="palabras_clave" value="Palabras clave (separadas por coma)" />
                    <TextInput id="palabras_clave" v-model="form.palabras_clave" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.palabras_clave" />
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="orden" value="Orden" />
                        <TextInput id="orden" v-model="form.orden" type="number" class="mt-1 block w-full" />
                    </div>
                    <label class="flex items-center gap-2 pt-7">
                        <input type="checkbox" v-model="form.activo" class="rounded border-slate-300 text-[#7c3aed] focus:ring-[#7c3aed]" />
                        <span class="text-sm text-slate-600">Activa (la usa el bot)</span>
                    </label>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link :href="route('faqs.index')" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700">Cancelar</Link>
                    <button type="submit" :disabled="form.processing" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90 disabled:opacity-50">
                        Actualizar pregunta
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
