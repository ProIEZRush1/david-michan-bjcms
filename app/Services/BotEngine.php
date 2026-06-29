<?php

namespace App\Services;

use App\Models\BotContact;
use App\Models\Cliente;
use App\Models\Faq;
use App\Models\Numero;
use App\Models\Pedido;
use App\Models\Plan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Bot de ventas en español para David Michan — venta de LÍNEAS / PLANES DE TELEFONÍA
 * por WhatsApp. Es una máquina de estados determinista (sin IA) sobre BotContact->step:
 *
 *   new → choosing → capturing → confirming → done   (+ el estado transversal "human")
 *
 * En CUALQUIER paso, si el mensaje coincide con la base de conocimiento (FAQs activas)
 * el bot responde la duda sin perder el hilo de la venta. Toda la copy vive en métodos
 * privados claramente etiquetados para que sea trivial editarla por cliente.
 */
class BotEngine
{
    // ---- estados de la máquina ------------------------------------------
    private const STEP_NEW = 'new';
    private const STEP_CHOOSING = 'choosing';
    private const STEP_CAPTURING = 'capturing';   // capturando datos del cliente (correo)
    private const STEP_CONFIRMING = 'confirming';
    private const STEP_DONE = 'done';
    private const STEP_HUMAN = 'human';

    public function __construct(private GatewayClient $gateway) {}

    public function handle(string $from, ?string $fromName, string $text): void
    {
        $contact = BotContact::firstOrCreate(['phone' => $from]);

        // Mantén fresco el nombre (pushName de WhatsApp) sin pisarlo con null.
        if (filled($fromName) && $contact->name !== $fromName) {
            $contact->name = $fromName;
            $contact->save();
        }

        $normalized = Str::lower(trim($text));

        // ESCALAMIENTO (cualquier paso): el cliente quiere una persona → transferir y callar.
        if ($this->wantsHuman($normalized)) {
            if ($contact->step !== self::STEP_HUMAN) {
                $this->setStep($contact, self::STEP_HUMAN);
                $this->reply($from, $this->copyHandoff());
            }

            return;
        }

        // Un humano ya tomó el chat → el bot permanece en silencio total.
        if ($contact->step === self::STEP_HUMAN) {
            return;
        }

        // "menu"/"menú"/"inicio" reinicia el embudo desde cualquier lado.
        if (in_array($normalized, ['menu', 'menú', 'inicio', 'hola'], true) && $contact->step === self::STEP_DONE) {
            $this->setStep($contact, self::STEP_NEW);
        } elseif (in_array($normalized, ['menu', 'menú'], true)) {
            $this->setStep($contact, self::STEP_NEW);
        }

        match ($contact->step) {
            self::STEP_CHOOSING => $this->onChoosing($contact, $from, $fromName, $normalized),
            self::STEP_CAPTURING => $this->onCapturing($contact, $from, $text, $normalized),
            self::STEP_CONFIRMING => $this->onConfirming($contact, $from, $fromName, $normalized),
            self::STEP_DONE => $this->onDone($contact, $from, $normalized),
            default => $this->onNew($contact, $from), // STEP_NEW / primer contacto / desconocido
        };
    }

    // ---- estados --------------------------------------------------------

    /** Saluda por nombre y presenta los planes activos; luego espera la elección. */
    private function onNew(BotContact $contact, string $from): void
    {
        $plans = $this->activePlans();
        if ($plans->isEmpty()) {
            $this->reply($from, $this->copyNoPlans());

            return;
        }

        $this->setStep($contact, self::STEP_CHOOSING);
        $this->reply($from, $this->copyGreeting($contact->name).$this->planList($plans).$this->copyAskChoice());
    }

    /** Empata el mensaje con un plan (por número de lista o nombre); crea el Pedido y pide el correo. */
    private function onChoosing(BotContact $contact, string $from, ?string $fromName, string $text): void
    {
        $plans = $this->activePlans();
        if ($plans->isEmpty()) {
            $this->reply($from, $this->copyNoPlans());

            return;
        }

        $plan = $this->matchPlan($plans, $text);
        if (! $plan) {
            // ¿Es una pregunta frecuente? Respóndela sin perder el hilo.
            if ($faq = $this->matchFaq($text)) {
                $this->reply($from, $faq->respuesta."\n\n".$this->copyBackToChoice());

                return;
            }

            $this->reply($from, $this->copyNoMatch().$this->planList($plans).$this->copyAskChoice());

            return;
        }

        $pedido = Pedido::create([
            'bot_contact_id' => $contact->id,
            'plan_id' => $plan->id,
            'cliente' => $fromName ?: $contact->name,
            'telefono' => $from,
            'total' => $plan->precio,
            'estado' => 'iniciado',
        ]);

        $data = $contact->data ?? [];
        $data['plan_id'] = $plan->id;
        $data['pedido_id'] = $pedido->id;
        $contact->data = $data;
        $contact->step = self::STEP_CAPTURING;
        $contact->save();

        $this->reply($from, $this->copyChosenAskEmail($plan));
    }

    /** Captura el correo del cliente (o lo omite) y pasa a confirmar. */
    private function onCapturing(BotContact $contact, string $from, string $rawText, string $text): void
    {
        $email = $this->extractEmail($rawText);
        $data = $contact->data ?? [];

        if ($email) {
            $data['email'] = $email;
        } elseif (! in_array($text, ['no', 'omitir', 'sin correo', 'despues', 'después', 'luego'], true)) {
            // No parece correo ni una omisión explícita: ¿es una FAQ?
            if ($faq = $this->matchFaq($text)) {
                $this->reply($from, $faq->respuesta."\n\n".$this->copyAskEmailRetry());

                return;
            }

            $this->reply($from, $this->copyAskEmailRetry());

            return;
        }

        $contact->data = $data;
        $contact->step = self::STEP_CONFIRMING;
        $contact->save();

        $plan = $this->planFromContact($contact);
        $this->reply($from, $this->copyConfirmPrompt($plan));
    }

    /** Afirmativo → cierra la venta (reserva número + link de pago); negativo → vuelve a elegir. */
    private function onConfirming(BotContact $contact, string $from, ?string $fromName, string $text): void
    {
        if ($this->isYes($text)) {
            $pedido = $this->pendingPedido($contact);
            $plan = $this->planFromContact($contact);
            $email = $contact->data['email'] ?? null;

            $cliente = Cliente::updateOrCreate(
                ['telefono' => $from],
                array_filter([
                    'nombre' => $fromName ?: $contact->name,
                    'email' => $email,
                ], fn ($v) => filled($v)),
            );

            // Reserva automática de un número disponible del inventario.
            $numero = $this->reservarNumero($plan);

            if ($pedido) {
                $pedido->update([
                    'cliente_id' => $cliente->id,
                    'numero_id' => $numero?->id,
                    'email' => $email,
                    'metodo_pago' => 'spei',
                    'referencia_pago' => $this->paymentLink($pedido),
                    'estado' => 'en_pago',
                ]);
            }

            $this->setStep($contact, self::STEP_DONE);
            $this->reply($from, $this->copyConfirmed($pedido, $numero));

            return;
        }

        if ($this->isNo($text)) {
            $this->setStep($contact, self::STEP_CHOOSING);
            $plans = $this->activePlans();
            $this->reply($from, $this->copyChangedMind().$this->planList($plans).$this->copyAskChoice());

            return;
        }

        // ¿FAQ mientras confirma? Responde y re-pregunta sí/no.
        if ($faq = $this->matchFaq($text)) {
            $this->reply($from, $faq->respuesta."\n\n".$this->copyConfirmRetry());

            return;
        }

        $this->reply($from, $this->copyConfirmRetry());
    }

    /** Pedido ya registrado → responde FAQs o cierra amablemente; "menu" reinicia. */
    private function onDone(BotContact $contact, string $from, string $text): void
    {
        if ($faq = $this->matchFaq($text)) {
            $this->reply($from, $faq->respuesta);

            return;
        }

        $this->reply($from, $this->copyAlreadyDone());
    }

    // ---- helpers de planes / números / faqs -----------------------------

    /** @return Collection<int,Plan> */
    private function activePlans(): Collection
    {
        return Plan::where('activo', true)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    private function planFromContact(BotContact $contact): ?Plan
    {
        $planId = $contact->data['plan_id'] ?? null;

        return $planId ? Plan::find($planId) : null;
    }

    /** Empata por número de lista (1-based) primero, luego por nombre (en cualquier sentido). */
    private function matchPlan(Collection $plans, string $text): ?Plan
    {
        $text = trim($text);

        if ($text !== '' && ctype_digit($text)) {
            return $plans->values()->get(((int) $text) - 1);
        }

        foreach ($plans as $plan) {
            $name = Str::lower(trim($plan->nombre));
            if ($name !== '' && (Str::contains($text, $name) || Str::contains($name, $text))) {
                return $plan;
            }
        }

        return null;
    }

    /** Reserva el primer número disponible (priorizando el del plan) y lo marca reservado. */
    private function reservarNumero(?Plan $plan): ?Numero
    {
        $numero = Numero::where('estado', 'disponible')
            ->when($plan, fn ($q) => $q->orderByRaw('CASE WHEN plan_id = ? THEN 0 ELSE 1 END', [$plan->id]))
            ->orderBy('id')
            ->first();

        if ($numero) {
            $numero->update(['estado' => 'reservado', 'plan_id' => $numero->plan_id ?: $plan?->id]);
        }

        return $numero;
    }

    /** Empata el texto con una FAQ activa por sus palabras clave. */
    private function matchFaq(string $text): ?Faq
    {
        $text = ' '.Str::lower(trim($text)).' ';
        if (trim($text) === '') {
            return null;
        }

        foreach (Faq::where('activo', true)->orderBy('orden')->orderBy('id')->get() as $faq) {
            foreach ($faq->keywords() as $kw) {
                if ($kw !== '' && Str::contains($text, $kw)) {
                    return $faq;
                }
            }
        }

        return null;
    }

    private function pendingPedido(BotContact $contact): ?Pedido
    {
        $pedidoId = $contact->data['pedido_id'] ?? null;
        if ($pedidoId && ($p = Pedido::find($pedidoId))) {
            return $p;
        }

        $planId = $contact->data['plan_id'] ?? null;

        return $contact->pedidos()
            ->where('estado', 'iniciado')
            ->when($planId, fn ($q) => $q->where('plan_id', $planId))
            ->latest('id')
            ->first()
            ?? $contact->pedidos()->where('estado', 'iniciado')->latest('id')->first();
    }

    private function extractEmail(string $text): ?string
    {
        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $text, $m)) {
            return Str::lower($m[0]);
        }

        return null;
    }

    private function paymentLink(Pedido $pedido): string
    {
        return rtrim((string) config('app.url'), '/').'/pago/'.$pedido->id.'-'.Str::random(8);
    }

    // ---- copy (cadenas en español, editables) ---------------------------

    private function copyGreeting(?string $name): string
    {
        $greeting = $name ? "¡Hola, {$name}! 👋" : '¡Hola! 👋';

        return $greeting.' Bienvenido a *'.config('app.name')."* 📱\n\n"
            ."Vendemos *líneas de teléfono* con planes a tu medida. Estos son nuestros planes disponibles:\n\n";
    }

    private function planList(Collection $plans): string
    {
        $lines = $plans->values()->map(function (Plan $plan, int $i) {
            $line = ($i + 1).'. *'.$plan->nombre.'* — '.$this->formatPrice($plan->precio).'/mes';
            $specs = collect([
                filled($plan->datos) ? $plan->datos.' de datos' : null,
                filled($plan->minutos) ? $plan->minutos.' min' : null,
                filled($plan->sms) ? $plan->sms.' SMS' : null,
            ])->filter()->implode(' · ');
            if ($specs !== '') {
                $line .= "\n   📶 ".$specs;
            }
            if (filled($plan->descripcion)) {
                $line .= "\n   ".$plan->descripcion;
            }

            return $line;
        });

        return $lines->implode("\n\n");
    }

    private function copyAskChoice(): string
    {
        return "\n\n¿Cuál te late? Respóndeme con el *número* o el *nombre* del plan. 🙂\n"
            .'También puedes preguntarme sobre cobertura, portabilidad o activación.';
    }

    private function copyBackToChoice(): string
    {
        return '¿Te gustaría contratar alguno de nuestros planes? Respóndeme con el *número* o el *nombre*. 🙂';
    }

    private function copyNoMatch(): string
    {
        return "No identifiqué ese plan. 🤔 Estos son los disponibles:\n\n";
    }

    private function copyChosenAskEmail(Plan $plan): string
    {
        return '¡Excelente elección! 🙌 Elegiste la línea *'.$plan->nombre.'* ('.$this->formatPrice($plan->precio)."/mes).\n\n"
            .'Para enviarte tu comprobante y el link de pago, ¿me compartes tu *correo electrónico*? '
            .'(o escribe *omitir* si prefieres continuar sin correo).';
    }

    private function copyAskEmailRetry(): string
    {
        return 'Por favor escríbeme un *correo válido* (ej. nombre@correo.com) o escribe *omitir* para continuar. 🙂';
    }

    private function copyConfirmPrompt(?Plan $plan): string
    {
        $nombre = $plan ? $plan->nombre : 'tu plan';
        $precio = $plan ? ' ('.$this->formatPrice($plan->precio).'/mes)' : '';

        return '¡Perfecto! 🎉 Vamos a contratar la línea *'.$nombre.'*'.$precio.".\n\n"
            .'¿Confirmas tu pedido? Responde *sí* para continuar o *no* para elegir otro plan.';
    }

    private function copyConfirmRetry(): string
    {
        return 'Para continuar, respóndeme *sí* para confirmar tu pedido o *no* para elegir otro plan. 🙂';
    }

    private function copyChangedMind(): string
    {
        return "¡Sin problema! 🙌 Aquí están los planes de nuevo:\n\n";
    }

    private function copyConfirmed(?Pedido $pedido, ?Numero $numero): string
    {
        $msg = "¡Listo! ✅ Registramos tu pedido";
        if ($pedido) {
            $msg .= ' *#'.$pedido->id.'*';
        }
        $msg .= ".\n\n";

        if ($numero) {
            $msg .= '📱 Te apartamos la línea *'.$numero->numero."* — queda lista en cuanto se confirme tu pago.\n\n";
        }

        if ($pedido && filled($pedido->referencia_pago)) {
            $msg .= "💳 Realiza tu pago aquí:\n".$pedido->referencia_pago."\n\n";
        }

        $msg .= 'En cuanto recibamos tu pago, activamos tu número y te avisamos por aquí. 🚀'
            ."\n\nSi quieres empezar de nuevo, escribe *menu*.";

        return $msg;
    }

    private function copyAlreadyDone(): string
    {
        return "Ya tienes un pedido en proceso ✅. En cuanto se confirme tu pago activamos tu línea. 🙌\n\n"
            .'¿Tienes una duda? Pregúntame sobre cobertura, portabilidad o activación, o escribe *menu* para empezar de nuevo.';
    }

    private function copyNoPlans(): string
    {
        return 'Gracias por escribir a *'.config('app.name').'* 🙌 En un momento un asesor te atiende personalmente.';
    }

    private function copyHandoff(): string
    {
        return '¡Claro que sí! 🙌 Te paso con uno de nuestros asesores para que te atienda personalmente. '
            .'En breve te contactan. ¡Quedo al pendiente! 😊';
    }

    // ---- matchers deterministas -----------------------------------------

    /** Confirmación afirmativa (descarta negativos explícitos). */
    private function isYes(string $text): bool
    {
        if ($this->isNo($text)) {
            return false;
        }

        if (preg_match('/\b(s[ií]|sip|sale|va|dale|ok|okay|claro|listo|correcto|adelante|confirm\w*|acept\w*|procede|lo quiero|contratar)\b/u', $text)) {
            return true;
        }

        return Str::contains($text, [
            'de acuerdo', 'me late', 'por supuesto', 'está bien', 'esta bien', 'hágale', 'hagale', 'perfecto',
        ]);
    }

    /** Negativo / rechazo explícito. */
    private function isNo(string $text): bool
    {
        return (bool) preg_match('/\b(no|nel|nop|nope|todav[ií]a no|a[uú]n no|aun no|por ahora no|'
            .'ahorita no|mejor no|otro plan|otra opci[oó]n|cambiar)\b/u', $text);
    }

    /** El cliente quiere una persona real / no quiere bot → handoff. */
    private function wantsHuman(string $text): bool
    {
        $text = ' '.trim($text).' ';

        return (bool) preg_match('/(asesor real|un asesor|una asesora|atenci[oó]n humana|'
            .'(hablar|hablo|comunicar|comunicarme|pasar|pasas?|p[aá]same|contactar|conectar|con[eé]ctame) con (un|una|alg[uú]ien|el|la)?\s*(humano|persona|asesor|asesora|agente|ejecutiv|alguien real|alguien|due[ñn]o|encargad)|'
            .'quiero (un|una|hablar con|que me atienda un|que me atienda una)?\s*(humano|persona|asesor|asesora|agente|alguien real)|'
            .'prefiero (un|una|hablar con|que me atienda)?\s*(humano|persona|asesor|asesora|agente|alguien)|'
            .'no quiero (hablar con)?\s*(un|una)?\s*(bot|ia|robot|inteligencia artificial|asistente)|'
            .'hablar con (un|una)?\s*(ia|bot|robot|inteligencia artificial)\s*no|'
            .'no me (interes|gust)\w*\s*(hablar con\s*(un|una)?\s*)?(ia|bot|robot|asistente|inteligencia artificial))/u', $text);
    }

    // ---- utilidades -----------------------------------------------------

    private function setStep(BotContact $contact, string $step): void
    {
        $contact->step = $step;
        $contact->save();
    }

    /** Formatea un precio en centavos como monto en MXN. */
    private function formatPrice(int $cents): string
    {
        return '$'.number_format($cents / 100, 0, '.', ',').' MXN';
    }

    private function reply(string $to, string $message): void
    {
        $this->gateway->send($to, $message);
    }
}
