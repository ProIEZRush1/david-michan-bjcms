<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Faq;
use App\Models\Numero;
use App\Models\Pedido;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_dashboard_carga_con_metricas(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_crear_plan_persiste(): void
    {
        $this->actingAs($this->admin())
            ->post(route('planes.store'), [
                'nombre' => 'Plan Test',
                'precio' => 199,
                'descripcion' => 'Demo',
                'datos' => '10 GB',
                'minutos' => 'Ilimitados',
                'sms' => 'Ilimitados',
                'tipo' => 'prepago',
                'vigencia_dias' => 30,
                'activo' => true,
                'orden' => 1,
            ])
            ->assertRedirect(route('planes.index'));

        $plan = Plan::where('nombre', 'Plan Test')->first();
        $this->assertNotNull($plan);
        $this->assertSame(19900, $plan->precio); // guardado en centavos
    }

    public function test_crear_numero_persiste(): void
    {
        $this->actingAs($this->admin())
            ->post(route('inventario.store'), [
                'numero' => '55 9999 0000',
                'lada' => '55',
                'tipo' => 'movil',
                'estado' => 'disponible',
            ])
            ->assertRedirect(route('inventario.index'));

        $this->assertDatabaseHas('numeros', ['numero' => '55 9999 0000', 'estado' => 'disponible']);
    }

    public function test_crear_cliente_persiste(): void
    {
        $this->actingAs($this->admin())
            ->post(route('clientes.store'), [
                'nombre' => 'Cliente Test',
                'telefono' => '5210000000001',
                'email' => 'cliente@test.com',
                'ciudad' => 'CDMX',
            ])
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', ['telefono' => '5210000000001', 'nombre' => 'Cliente Test']);
    }

    public function test_crear_faq_persiste(): void
    {
        $this->actingAs($this->admin())
            ->post(route('faqs.store'), [
                'pregunta' => '¿Pregunta de prueba?',
                'respuesta' => 'Respuesta de prueba.',
                'palabras_clave' => 'prueba, test',
                'activo' => true,
                'orden' => 0,
            ])
            ->assertRedirect(route('faqs.index'));

        $this->assertDatabaseHas('faqs', ['pregunta' => '¿Pregunta de prueba?']);
    }

    public function test_crear_pedido_persiste_y_calcula_total(): void
    {
        $plan = Plan::create([
            'nombre' => 'P', 'precio' => 24900, 'tipo' => 'prepago', 'vigencia_dias' => 30, 'activo' => true, 'orden' => 1,
        ]);

        $this->actingAs($this->admin())
            ->post(route('pedidos.store'), [
                'plan_id' => $plan->id,
                'cliente' => 'Comprador',
                'telefono' => '5210000000002',
                'email' => 'comprador@test.com',
                'estado' => 'iniciado',
            ])
            ->assertRedirect(route('pedidos.index'));

        $pedido = Pedido::where('telefono', '5210000000002')->first();
        $this->assertNotNull($pedido);
        $this->assertSame(24900, $pedido->total);
        $this->assertDatabaseHas('clientes', ['telefono' => '5210000000002']);
    }

    public function test_marcar_pagado_asigna_numero_automaticamente(): void
    {
        $plan = Plan::create(['nombre' => 'P', 'precio' => 14900, 'tipo' => 'prepago', 'vigencia_dias' => 30, 'activo' => true, 'orden' => 1]);
        $numero = Numero::create(['numero' => '55 1111 2222', 'estado' => 'disponible', 'tipo' => 'movil']);
        $pedido = Pedido::create([
            'plan_id' => $plan->id, 'cliente' => 'X', 'telefono' => '521999', 'total' => 14900, 'estado' => 'en_pago',
        ]);

        $this->actingAs($this->admin())
            ->put(route('pedidos.update', $pedido->id), [
                'plan_id' => $plan->id,
                'cliente' => 'X',
                'telefono' => '521999',
                'estado' => 'pagado',
            ])
            ->assertRedirect(route('pedidos.index'));

        $pedido->refresh();
        $numero->refresh();
        $this->assertSame($numero->id, $pedido->numero_id);
        $this->assertSame('asignado', $numero->estado);
        $this->assertSame('numero_asignado', $pedido->estado);
    }

    public function test_modulos_requieren_autenticacion(): void
    {
        $this->get(route('planes.index'))->assertRedirect(route('login'));
        $this->get(route('pedidos.index'))->assertRedirect(route('login'));
    }
}
