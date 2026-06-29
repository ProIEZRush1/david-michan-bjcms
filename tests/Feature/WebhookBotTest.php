<?php

namespace Tests\Feature;

use App\Models\BotContact;
use App\Models\Numero;
use App\Models\Pedido;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookBotTest extends TestCase
{
    use RefreshDatabase;

    private function seedPlanes(): void
    {
        Plan::create(['nombre' => 'Esencial', 'precio' => 14900, 'datos' => '5 GB', 'tipo' => 'prepago', 'vigencia_dias' => 30, 'activo' => true, 'orden' => 1]);
        Plan::create(['nombre' => 'Plus', 'precio' => 24900, 'datos' => '15 GB', 'tipo' => 'prepago', 'vigencia_dias' => 30, 'activo' => true, 'orden' => 2]);
        Numero::create(['numero' => '55 0000 1111', 'estado' => 'disponible', 'tipo' => 'movil']);
    }

    private function inbound(string $from, string $text, string $name = 'Cliente'): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['x-gateway-token' => config('bot.gateway_token')])
            ->postJson('/api/wa/inbound', [
                'from' => $from,
                'fromName' => $name,
                'text' => $text,
                'isGroup' => false,
            ]);
    }

    public function test_webhook_rechaza_token_invalido(): void
    {
        $this->withHeaders(['x-gateway-token' => 'malo'])
            ->postJson('/api/wa/inbound', ['from' => '521', 'text' => 'hola'])
            ->assertStatus(401);
    }

    public function test_mensaje_entrante_genera_respuesta_del_bot(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $this->seedPlanes();

        $this->inbound('5211234500001', 'hola')->assertOk();

        // El contacto se registró y avanzó al paso de elección.
        $contact = BotContact::where('phone', '5211234500001')->first();
        $this->assertNotNull($contact);
        $this->assertSame('choosing', $contact->step);

        // El bot intentó responder por el gateway con el nombre del negocio.
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/send')
                && str_contains($request['text'] ?? '', config('app.name'))
                && str_contains($request['text'] ?? '', 'planes');
        });
    }

    public function test_flujo_completo_cierra_venta_y_reserva_numero(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $this->seedPlanes();
        $from = '5211234500002';

        $this->inbound($from, 'hola')->assertOk();
        $this->inbound($from, '2')->assertOk();            // elige Plus
        $this->inbound($from, 'cliente@correo.com')->assertOk(); // captura correo
        $this->inbound($from, 'si')->assertOk();           // confirma

        $pedido = Pedido::where('telefono', $from)->first();
        $this->assertNotNull($pedido);
        $this->assertSame('en_pago', $pedido->estado);
        $this->assertNotNull($pedido->numero_id);
        $this->assertSame(24900, $pedido->total);

        // El número reservado salió del pool de disponibles.
        $this->assertSame('reservado', Numero::find($pedido->numero_id)->estado);
    }

    public function test_bot_responde_pregunta_frecuente(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $this->seedPlanes();
        \App\Models\Faq::create([
            'pregunta' => '¿Portabilidad?',
            'respuesta' => 'La portabilidad es gratis y tarda 24 horas.',
            'palabras_clave' => 'portabilidad, portar',
            'activo' => true,
            'orden' => 1,
        ]);
        $from = '5211234500003';

        $this->inbound($from, 'hola')->assertOk();
        $this->inbound($from, 'quiero portabilidad')->assertOk();

        Http::assertSent(fn ($request) => str_contains($request['text'] ?? '', 'portabilidad es gratis'));
    }

    public function test_escalado_a_humano_silencia_el_bot(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $this->seedPlanes();
        $from = '5211234500004';

        $this->inbound($from, 'quiero hablar con un asesor humano')->assertOk();

        $contact = BotContact::where('phone', $from)->first();
        $this->assertSame('human', $contact->step);
    }
}
