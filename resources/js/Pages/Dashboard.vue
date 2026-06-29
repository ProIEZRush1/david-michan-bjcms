<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    metrics: { type: Object, default: () => ({}) },
    ultimosPedidos: { type: Array, default: () => [] },
});

const page = usePage();
const businessName = computed(() => page.props.name ?? 'David Michan');
const userFirstName = computed(() => {
    const name = (page.props.auth?.user?.name ?? '').trim();
    return name ? name.split(/\s+/)[0] : '';
});

const money = (cents) =>
    '$' + ((cents ?? 0) / 100).toLocaleString('es-MX', { minimumFractionDigits: 0 });

const m = computed(() => props.metrics ?? {});

const modulos = computed(() => [
    {
        label: 'Planes de telefonía',
        value: m.value.planes ?? 0,
        hint: `${m.value.planesActivos ?? 0} activos`,
        route: 'planes.index',
        emoji: '📱',
        gradient: 'from-[#7c3aed] to-[#a855f7]',
    },
    {
        label: 'Inventario de números',
        value: m.value.numeros ?? 0,
        hint: `${m.value.numerosDisponibles ?? 0} disponibles`,
        route: 'inventario.index',
        emoji: '🔢',
        gradient: 'from-[#a21caf] to-[#c026d3]',
    },
    {
        label: 'Pedidos',
        value: m.value.pedidos ?? 0,
        hint: `${m.value.pedidosAbiertos ?? 0} en proceso`,
        route: 'pedidos.index',
        emoji: '🧾',
        gradient: 'from-[#7c3aed] to-[#c026d3]',
    },
    {
        label: 'Clientes',
        value: m.value.clientes ?? 0,
        hint: 'Registrados',
        route: 'clientes.index',
        emoji: '👥',
        gradient: 'from-[#c026d3] to-[#db2777]',
    },
    {
        label: 'Preguntas frecuentes',
        value: m.value.faqs ?? 0,
        hint: 'Activas en el bot',
        route: 'faqs.index',
        emoji: '💬',
        gradient: 'from-[#7c3aed] to-[#a855f7]',
    },
    {
        label: 'Conversaciones',
        value: m.value.conversaciones ?? 0,
        hint: `${m.value.enEspera ?? 0} con asesor`,
        route: 'conversaciones.index',
        emoji: '🤖',
        gradient: 'from-[#a21caf] to-[#c026d3]',
    },
]);

const estadoColor = (estado) =>
    ({
        iniciado: 'bg-slate-100 text-slate-600',
        en_pago: 'bg-amber-100 text-amber-700',
        pagado: 'bg-blue-100 text-blue-700',
        numero_asignado: 'bg-violet-100 text-violet-700',
        entregado: 'bg-green-100 text-green-700',
        cancelado: 'bg-rose-100 text-rose-700',
    })[estado] ?? 'bg-slate-100 text-slate-600';
</script>

<template>
    <Head title="Panel" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">
                Panel de control
            </h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-8">
            <!-- Hero -->
            <section
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#7c3aed] to-[#c026d3] p-8 text-white shadow-xl shadow-fuchsia-500/20 sm:p-10"
            >
                <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-fuchsia-300/20 blur-2xl"></div>
                <div class="relative">
                    <p class="text-sm font-medium uppercase tracking-widest text-white/70">
                        Bot de ventas por WhatsApp
                    </p>
                    <h1 class="mt-3 text-3xl font-extrabold leading-tight sm:text-4xl">
                        Hola<span v-if="userFirstName">, {{ userFirstName }}</span> 👋
                    </h1>
                    <p class="mt-3 max-w-2xl text-base text-white/85">
                        Bienvenido al panel de <span class="font-semibold">{{ businessName }}</span>.
                        Administra tus planes, inventario de líneas, pedidos y la conversación
                        de tu bot vendedor de líneas de teléfono — todo en un solo lugar.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            :href="route('conectar')"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-[#7c3aed] shadow-lg transition hover:bg-white/90"
                        >
                            🔗 Conectar WhatsApp
                        </Link>
                        <Link
                            :href="route('pedidos.index')"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/40 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
                        >
                            Ver pedidos
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Ingresos destacados -->
            <section class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">Ingresos confirmados</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-800">{{ money(m.ingresos) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Pedidos pagados, asignados y entregados</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">Líneas disponibles</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-800">{{ m.numerosDisponibles ?? 0 }}</p>
                    <p class="mt-1 text-xs text-slate-400">Listas para asignar en el inventario</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">Chats con asesor</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-800">{{ m.enEspera ?? 0 }}</p>
                    <p class="mt-1 text-xs text-slate-400">Conversaciones escaladas a un humano</p>
                </div>
            </section>

            <!-- Módulos -->
            <section>
                <h3 class="mb-4 text-lg font-bold text-slate-800">Módulos del sistema</h3>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="mod in modulos"
                        :key="mod.label"
                        :href="route(mod.route)"
                        class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg"
                    >
                        <div class="flex items-start justify-between">
                            <span
                                :class="['flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br text-2xl shadow-md', mod.gradient]"
                            >
                                {{ mod.emoji }}
                            </span>
                            <span class="text-sm font-semibold text-[#7c3aed] opacity-0 transition group-hover:opacity-100">
                                Administrar →
                            </span>
                        </div>
                        <p class="mt-4 text-3xl font-extrabold text-slate-800">{{ mod.value }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-600">{{ mod.label }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ mod.hint }}</p>
                    </Link>
                </div>
            </section>

            <!-- Últimos pedidos -->
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800">Últimos pedidos</h3>
                    <Link :href="route('pedidos.index')" class="text-sm font-semibold text-[#7c3aed] hover:text-[#c026d3]">
                        Ver todos
                    </Link>
                </div>

                <div v-if="ultimosPedidos.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="px-3 py-2">Cliente</th>
                                <th class="px-3 py-2">Plan</th>
                                <th class="px-3 py-2">Línea</th>
                                <th class="px-3 py-2">Total</th>
                                <th class="px-3 py-2">Estado</th>
                                <th class="px-3 py-2">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="p in ultimosPedidos" :key="p.id" class="text-slate-700">
                                <td class="px-3 py-3 font-medium">{{ p.cliente || '—' }}</td>
                                <td class="px-3 py-3">{{ p.plan || '—' }}</td>
                                <td class="px-3 py-3">{{ p.numero || '—' }}</td>
                                <td class="px-3 py-3">{{ money(p.total) }}</td>
                                <td class="px-3 py-3">
                                    <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', estadoColor(p.estado)]">
                                        {{ p.estado_label }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-xs text-slate-400">{{ p.creado }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="py-8 text-center text-sm text-slate-400">
                    Aún no hay pedidos. En cuanto tu bot cierre una venta, aparecerá aquí.
                </p>
            </section>

            <p class="text-center text-xs text-slate-400">
                Desarrollado por <span class="font-semibold text-slate-500">Overcloud</span>
            </p>
        </div>
    </AuthenticatedLayout>
</template>
