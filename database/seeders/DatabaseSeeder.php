<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Faq;
use App\Models\Numero;
use App\Models\Pedido;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ---- Usuario administrador (idempotente) ------------------------
        User::updateOrCreate(
            ['email' => 'david-michan@overcloud.us'],
            [
                'name' => 'David Michan',
                'password' => Hash::make('7m6bttZH8dZA'),
                'email_verified_at' => now(),
            ],
        );

        // ---- Planes de telefonía ----------------------------------------
        $planes = [
            ['nombre' => 'Esencial', 'precio' => 14900, 'datos' => '5 GB', 'minutos' => 'Ilimitados', 'sms' => 'Ilimitados', 'tipo' => 'prepago', 'orden' => 1,
                'descripcion' => 'Ideal para uso diario: redes sociales y mensajería sin preocupaciones.'],
            ['nombre' => 'Plus', 'precio' => 24900, 'datos' => '15 GB', 'minutos' => 'Ilimitados', 'sms' => 'Ilimitados', 'tipo' => 'prepago', 'orden' => 2,
                'descripcion' => 'El más popular: datos de sobra para trabajar y entretenerte.'],
            ['nombre' => 'Pro', 'precio' => 39900, 'datos' => '30 GB', 'minutos' => 'Ilimitados', 'sms' => 'Ilimitados', 'tipo' => 'pospago', 'orden' => 3,
                'descripcion' => 'Máximo rendimiento: streaming, hotspot y prioridad de red.'],
            ['nombre' => 'Ilimitado', 'precio' => 59900, 'datos' => 'Ilimitado', 'minutos' => 'Ilimitados', 'sms' => 'Ilimitados', 'tipo' => 'pospago', 'orden' => 4,
                'descripcion' => 'Sin límites de datos a máxima velocidad. Para los que lo quieren todo.'],
        ];

        foreach ($planes as $p) {
            Plan::updateOrCreate(
                ['nombre' => $p['nombre']],
                array_merge($p, ['activo' => true, 'vigencia_dias' => 30]),
            );
        }

        $planEsencial = Plan::where('nombre', 'Esencial')->first();
        $planPlus = Plan::where('nombre', 'Plus')->first();
        $planPro = Plan::where('nombre', 'Pro')->first();

        // ---- Inventario de números --------------------------------------
        $numeros = [
            ['numero' => '55 1010 2030', 'lada' => '55', 'estado' => 'disponible'],
            ['numero' => '55 1122 3344', 'lada' => '55', 'estado' => 'disponible'],
            ['numero' => '55 2244 6688', 'lada' => '55', 'estado' => 'disponible'],
            ['numero' => '33 3050 7090', 'lada' => '33', 'estado' => 'disponible'],
            ['numero' => '33 4060 8020', 'lada' => '33', 'estado' => 'disponible'],
            ['numero' => '81 5070 9010', 'lada' => '81', 'estado' => 'disponible'],
            ['numero' => '81 6080 1030', 'lada' => '81', 'estado' => 'disponible'],
            ['numero' => '55 7788 9900', 'lada' => '55', 'estado' => 'asignado'],
            ['numero' => '33 1234 5678', 'lada' => '33', 'estado' => 'reservado'],
        ];

        foreach ($numeros as $n) {
            Numero::updateOrCreate(
                ['numero' => $n['numero']],
                array_merge($n, ['tipo' => 'movil']),
            );
        }

        // ---- Preguntas frecuentes (base de conocimiento del bot) --------
        $faqs = [
            ['pregunta' => '¿Tienen cobertura en mi ciudad?', 'orden' => 1,
                'palabras_clave' => 'cobertura, señal, donde tienen señal, red',
                'respuesta' => '📡 Contamos con cobertura nacional en las principales ciudades de México (CDMX, Guadalajara, Monterrey y más). Compártenos tu ciudad y te confirmamos al instante.'],
            ['pregunta' => '¿Puedo conservar mi número (portabilidad)?', 'orden' => 2,
                'palabras_clave' => 'portabilidad, conservar mi numero, portar, cambiar de compañia, mismo numero',
                'respuesta' => '🔁 ¡Claro! La portabilidad es gratis y tarda menos de 24 horas. Solo necesitas tu número actual y tu identificación. Nosotros hacemos todo el trámite por ti.'],
            ['pregunta' => '¿Cómo activo mi línea?', 'orden' => 3,
                'palabras_clave' => 'activar, activacion, como activo, encender la linea',
                'respuesta' => '⚡ La activación es inmediata: en cuanto confirmamos tu pago, te enviamos tu número activo por este mismo chat con los pasos para insertar tu SIM o eSIM.'],
            ['pregunta' => '¿Qué métodos de pago aceptan?', 'orden' => 4,
                'palabras_clave' => 'metodos de pago, como pago, tarjeta, transferencia, spei',
                'respuesta' => '💳 Aceptamos tarjeta (Stripe), PayPal y transferencia SPEI. Al confirmar tu pedido te enviamos un link de pago seguro aquí mismo.'],
            ['pregunta' => '¿Los planes tienen contrato forzoso?', 'orden' => 5,
                'palabras_clave' => 'contrato, forzoso, permanencia, plazo, cancelar',
                'respuesta' => '✅ Nuestros planes prepago son sin contrato ni permanencia: pagas mes con mes y cancelas cuando quieras, sin penalización.'],
        ];

        foreach ($faqs as $f) {
            Faq::updateOrCreate(
                ['pregunta' => $f['pregunta']],
                array_merge($f, ['activo' => true]),
            );
        }

        // ---- Clientes de ejemplo ----------------------------------------
        $clientes = [
            ['nombre' => 'María Fernanda López', 'telefono' => '5215512345678', 'email' => 'mafer.lopez@example.com', 'ciudad' => 'CDMX'],
            ['nombre' => 'Jorge Ramírez', 'telefono' => '5213398765432', 'email' => 'jorge.ramirez@example.com', 'ciudad' => 'Guadalajara'],
            ['nombre' => 'Ana Sofía Torres', 'telefono' => '5218111223344', 'email' => 'ana.torres@example.com', 'ciudad' => 'Monterrey'],
        ];

        foreach ($clientes as $c) {
            Cliente::updateOrCreate(['telefono' => $c['telefono']], $c);
        }

        // ---- Pedidos de ejemplo (varios estados del flujo) --------------
        $mafer = Cliente::where('telefono', '5215512345678')->first();
        $jorge = Cliente::where('telefono', '5213398765432')->first();
        $ana = Cliente::where('telefono', '5218111223344')->first();
        $numAsignado = Numero::where('numero', '55 7788 9900')->first();
        $numReservado = Numero::where('numero', '33 1234 5678')->first();

        $pedidos = [
            ['cliente_obj' => $mafer, 'plan' => $planPro, 'estado' => 'entregado', 'numero_id' => $numAsignado?->id, 'metodo_pago' => 'stripe'],
            ['cliente_obj' => $jorge, 'plan' => $planPlus, 'estado' => 'en_pago', 'numero_id' => $numReservado?->id, 'metodo_pago' => 'spei'],
            ['cliente_obj' => $ana, 'plan' => $planEsencial, 'estado' => 'iniciado', 'numero_id' => null, 'metodo_pago' => null],
        ];

        foreach ($pedidos as $pd) {
            $cli = $pd['cliente_obj'];
            if (! $cli || ! $pd['plan']) {
                continue;
            }
            Pedido::updateOrCreate(
                ['cliente_id' => $cli->id, 'plan_id' => $pd['plan']->id],
                [
                    'cliente' => $cli->nombre,
                    'telefono' => $cli->telefono,
                    'email' => $cli->email,
                    'numero_id' => $pd['numero_id'],
                    'total' => $pd['plan']->precio,
                    'metodo_pago' => $pd['metodo_pago'],
                    'estado' => $pd['estado'],
                ],
            );
        }
    }
}
