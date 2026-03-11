<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

// ────────────────────────────────────────────────────────────────
//  RUTA DE PRUEBA  →  http://localhost:8000/test-whatsapp
// ────────────────────────────────────────────────────────────────
Route::get('/test-whatsapp', function () {

    // Leer número admin de la DB
    $contact   = DB::table('contact_infos')->first();
    $adminWA   = $contact ? preg_replace('/[^0-9]/', '', $contact->whatsapp ?? '51931158218') : '51931158218';

    // Crear pedido de prueba en DB
    $orderNum  = 'WEB-TEST-' . strtoupper(substr(md5(microtime()), 0, 5));
    $orderId   = DB::table('orders')->insertGetId([
        'order_number'               => $orderNum,
        'source'                     => 'web',
        'customer_name'              => 'Cliente Demo Tienda',
        'customer_email'             => 'demo@herlinso.pe',
        'customer_phone'             => '987654321',
        'delivery_type'              => 'agency',
        'agency_type'                => 'olva',
        'agency_name'                => 'OLVA - San Isidro Centro',
        'agency_address'             => 'Jr. Petit Thouars 120, San Isidro',
        'items'                      => json_encode([
            ['name' => 'Sauvage EDT 100ml',     'price' => 220, 'quantity' => 1],
            ['name' => 'Bleu de Chanel EDP',    'price' => 310, 'quantity' => 2],
        ]),
        'subtotal'                   => 840.00,
        'tax'                        => 0,
        'shipping_cost'              => 0,
        'total'                      => 840.00,
        'payment_method'             => 'yape',
        'payment_status'             => 'pending',
        'status'                     => 'pending',
        'notes'                      => '--- PEDIDO DE PRUEBA WEB (eliminar) ---',
        'requires_admin_confirmation'=> 1,
        'created_at'                 => now(),
        'updated_at'                 => now(),
    ]);

    // Construir mensaje WhatsApp idéntico al del frontend
    $itemsList   = "  • Sauvage EDT 100ml x1 — S/ 220.00\n  • Bleu de Chanel EDP x2 — S/ 620.00";
    $deliveryInfo = "📦 Agencia: OLVA\n🏪 Nombre: OLVA - San Isidro Centro\n📍 Dirección: Jr. Petit Thouars 120, San Isidro";
    $message     = "🛍️ *NUEVO PEDIDO #{$orderNum}*\n\n"
                 . "👤 *Cliente:* Cliente Demo Tienda\n"
                 . "📱 *Teléfono:* 987654321\n"
                 . "📧 *Email:* demo@herlinso.pe\n\n"
                 . "🧾 *Productos:*\n{$itemsList}\n\n"
                 . "💰 *Total: S/ 840.00*\n\n"
                 . "{$deliveryInfo}\n\n"
                 . "💳 *Método de pago:* Yape\n\n"
                 . "Hola, acabo de hacer un pedido en HERLINSO PERFÜMERÍA. ¿Podrían confirmar el envío?";

    $waUrl = "https://wa.me/{$adminWA}?text=" . urlencode($message);

    return response("<!DOCTYPE html>
<html lang='es'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>Prueba WhatsApp — HERLINSO</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
           background:#0f0f0f; color:#fff; min-height:100vh;
           display:flex; align-items:center; justify-content:center; padding:20px; }
    .card { background:#1a1a1a; border:1px solid #2a2a2a; border-radius:20px;
            padding:40px; max-width:480px; width:100%; text-align:center; }
    .badge { background:#22c55e20; color:#22c55e; border:1px solid #22c55e40;
             display:inline-block; padding:4px 14px; border-radius:20px;
             font-size:12px; font-weight:600; margin-bottom:20px; letter-spacing:.05em; }
    h1 { font-size:22px; margin-bottom:6px; color:#fff; }
    .order-num { color:#f59e0b; font-size:15px; margin-bottom:24px; font-weight:600; }
    .msg-box { background:#111; border:1px solid #2a2a2a; border-radius:12px;
               padding:16px; text-align:left; font-size:13px; line-height:1.7;
               color:#ccc; white-space:pre-wrap; margin-bottom:28px; }
    .wa-btn { display:flex; align-items:center; justify-content:center; gap:10px;
              background:#22c55e; color:#fff; font-weight:700; font-size:16px;
              padding:16px 32px; border-radius:14px; text-decoration:none;
              transition:background .2s; }
    .wa-btn:hover { background:#16a34a; }
    .wa-btn svg { width:24px; height:24px; flex-shrink:0; }
    .note { color:#555; font-size:12px; margin-top:16px; line-height:1.5; }
    .delete-btn { margin-top:20px; background:#ef444420; color:#ef4444;
                  border:1px solid #ef444440; padding:8px 20px; border-radius:8px;
                  font-size:12px; cursor:pointer; text-decoration:none; display:inline-block; }
    .delete-btn:hover { background:#ef444440; }
  </style>
</head>
<body>
  <div class='card'>
    <div class='badge'>🧪 PRUEBA INTERNA</div>
    <h1>Pedido de prueba generado</h1>
    <p class='order-num'>#{$orderNum} — ID {$orderId} — S/ 840.00</p>

    <div class='msg-box'>🛍️ *NUEVO PEDIDO #{$orderNum}*

👤 *Cliente:* Cliente Demo Tienda
📱 *Teléfono:* 987654321
📧 *Email:* demo@herlinso.pe

🧾 *Productos:*
  • Sauvage EDT 100ml x1 — S/ 220.00
  • Bleu de Chanel EDP x2 — S/ 620.00

💰 *Total: S/ 840.00*

📦 Agencia: OLVA
🏪 Nombre: OLVA - San Isidro Centro
📍 Dirección: Jr. Petit Thouars 120, San Isidro

💳 *Método de pago:* Yape

Hola, acabo de hacer un pedido en HERLINSO PERFÜMERÍA. ¿Podrían confirmar el envío?</div>

    <a class='wa-btn' href='{$waUrl}' target='_blank'>
      <svg viewBox='0 0 24 24' fill='currentColor'>
        <path d='M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z'/>
      </svg>
      Abrir WhatsApp con este pedido
    </a>
    <p class='note'>➡ WhatsApp se abre al número <strong style='color:#f59e0b'>+{$adminWA}</strong> con el mensaje listo.<br>Solo presiona <strong>Enviar</strong>.</p>
    <a class='delete-btn' href='/test-whatsapp/delete/{$orderId}'>🗑 Eliminar pedido de prueba</a>
  </div>
</body>
</html>", 200, ['Content-Type' => 'text/html']);
});

// Eliminar pedido de prueba
Route::get('/test-whatsapp/delete/{id}', function ($id) {
    DB::table('orders')->where('id', $id)
      ->where('notes', 'like', '%PEDIDO DE PRUEBA WEB%')
      ->delete();
    return response('<script>alert("Pedido de prueba eliminado ✅"); window.history.back();</script>',
                    200, ['Content-Type' => 'text/html']);
});
