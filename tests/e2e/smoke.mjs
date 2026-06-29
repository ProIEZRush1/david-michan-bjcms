// Prueba de navegador headless REAL (Playwright + Chromium) contra la app corriendo
// localmente. Inicia sesión como el admin, crea un registro por la INTERFAZ en cada
// módulo, confirma que aparece en la tabla y que PERSISTE tras recargar. Sale con
// código 0 sólo si todo pasa en verde.
//
//   node tests/e2e/smoke.mjs
//
import { chromium } from 'playwright';

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8123';
const EMAIL = 'david-michan@overcloud.us';
const PASSWORD = '7m6bttZH8dZA';
const stamp = Date.now().toString().slice(-6);

function assert(cond, msg) {
    if (!cond) throw new Error('ASSERT FAILED: ' + msg);
}

async function run() {
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage'],
    });
    const context = await browser.newContext();
    const page = await context.newPage();
    page.setDefaultTimeout(20000);

    // Espera a que un texto aparezca en el DOM (no depende de pintado/visibilidad).
    const waitForText = (text) =>
        page.waitForFunction(
            (t) => document.body && (document.body.textContent || '').includes(t),
            text,
            { timeout: 20000 },
        );

    try {
        // ---- 1. Login ------------------------------------------------------
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#email');
        await page.fill('#email', EMAIL);
        await page.fill('#password', PASSWORD);
        await Promise.all([
            page.waitForURL('**/dashboard', { timeout: 15000 }),
            page.getByRole('button', { name: /iniciar sesión/i }).click(),
        ]);
        await waitForText('David Michan');
        const dash = await page.content();
        assert(/David Michan/.test(dash), 'el dashboard debe mostrar "David Michan"');
        assert(!/You're logged in/i.test(dash), 'no debe aparecer "You\'re logged in"');
        assert(!/\bLaravel\b/.test(dash), 'no debe aparecer "Laravel" en el dashboard');
        console.log('✓ Login y dashboard de David Michan OK');

        // Helper de creación por la UI + verificación de persistencia.
        async function modulo(nombre, listUrl, createLinkText, fillFn, expectText) {
            await page.goto(`${BASE}${listUrl}`, { waitUntil: 'domcontentloaded' });
            await waitForText(createLinkText);
            await page.click(`text=${createLinkText}`);
            await page.waitForSelector('button[type=submit]');
            await fillFn();
            await Promise.all([
                page.waitForURL(`**${listUrl}`, { timeout: 20000 }),
                page.click('button[type=submit]'),
            ]);
            await waitForText(expectText);
            console.log(`✓ ${nombre}: creado por UI y visible en la tabla`);
            // Recarga → persistencia real en BD.
            await page.reload({ waitUntil: 'domcontentloaded' });
            await waitForText(expectText);
            console.log(`✓ ${nombre}: persiste tras recargar`);
        }

        // ---- 2. Planes -----------------------------------------------------
        const planNombre = `Plan E2E ${stamp}`;
        await modulo('Planes', '/planes', 'Nuevo plan', async () => {
            await page.fill('#nombre', planNombre);
            await page.fill('#precio', '199');
            await page.fill('#datos', '12 GB');
        }, planNombre);

        // ---- 3. Inventario -------------------------------------------------
        const numero = `55 ${stamp} 77`;
        await modulo('Inventario', '/inventario', 'Nuevo número', async () => {
            await page.fill('#numero', numero);
            await page.fill('#lada', '55');
        }, numero);

        // ---- 4. Clientes ---------------------------------------------------
        const clienteNombre = `Cliente E2E ${stamp}`;
        await modulo('Clientes', '/clientes', 'Nuevo cliente', async () => {
            await page.fill('#nombre', clienteNombre);
            await page.fill('#telefono', `52155${stamp}01`);
            await page.fill('#email', `e2e${stamp}@correo.com`);
        }, clienteNombre);

        // ---- 5. Preguntas frecuentes --------------------------------------
        const pregunta = `¿Pregunta E2E ${stamp}?`;
        await modulo('Preguntas frecuentes', '/faqs', 'Nueva pregunta', async () => {
            await page.fill('#pregunta', pregunta);
            await page.fill('#respuesta', 'Respuesta automática de prueba E2E.');
            await page.fill('#palabras_clave', `e2e${stamp}`);
        }, pregunta);

        // ---- 6. Pedidos ----------------------------------------------------
        const pedidoCliente = `Comprador E2E ${stamp}`;
        await modulo('Pedidos', '/pedidos', 'Nuevo pedido', async () => {
            await page.selectOption('#plan_id', { index: 0 });
            await page.fill('#cliente', pedidoCliente);
            await page.fill('#telefono', `52155${stamp}02`);
        }, pedidoCliente);

        // ---- 7. Anti-genérico final ---------------------------------------
        await page.goto(`${BASE}/dashboard`, { waitUntil: 'domcontentloaded' });
        await waitForText('David Michan');
        const finalBody = await page.content();
        assert(!/\bLaravel\b/.test(finalBody), 'el dashboard final no debe contener "Laravel"');
        console.log('✓ Sin rastros genéricos (Laravel)');

        console.log('\n✅ TODAS LAS PRUEBAS E2E PASARON');
        await browser.close();
        process.exit(0);
    } catch (err) {
        console.error('\n❌ FALLO E2E:', err.message);
        try {
            await page.screenshot({ path: '/tmp/e2e-failure.png' }).catch(() => {});
            console.error('URL actual:', page.url());
        } catch (_) {}
        await browser.close();
        process.exit(1);
    }
}

run();
