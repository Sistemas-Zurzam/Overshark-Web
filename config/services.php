<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    // Pagos con tarjeta (Izipay, formulario incrustado REST V4). Las llaves de TEST y las de PRODUCCIÓN salen del
    // Back Office (Configuración > Tienda > Llaves de API REST): se cambian en el .env, las URLs son las mismas.
    //  - shop_id: ID de tienda (8 dígitos) · password: contraseña de la API REST (TEST o PRODUCCIÓN)
    //  - public_key: llave pública · hmac_key: clave HMAC-SHA-256 (valida la firma que vuelve al navegador)
    'izipay' => [
        'shop_id' => env('IZIPAY_SHOP_ID'),
        'password' => env('IZIPAY_PASSWORD'),
        'public_key' => env('IZIPAY_PUBLIC_KEY'),
        'hmac_key' => env('IZIPAY_HMAC_KEY'),
        'api_url' => env('IZIPAY_API_URL', 'https://api.micuentaweb.pe/api-payment/V4'),
        'static_url' => env('IZIPAY_STATIC_URL', 'https://static.micuentaweb.pe/static'),
        'timeout' => (int) env('IZIPAY_TIMEOUT', 30),
    ],

    // Modo simulación (App\Services\Payments\PaymentSimulator): aprueba o rechaza pagos de prueba sin cobrar y,
    // sin CRM configurado, simula la venta en el CRM. Nunca funciona con APP_ENV=production.
    'payments' => [
        'simulate' => (bool) env('PAYMENTS_SIMULATE', false),
        // Minutos que un pedido espera el pago con tarjeta antes de cancelarse.
        'wait_minutes' => (int) env('PAYMENTS_WAIT_MINUTES', 30),
    ],

    'zazu' => [
        'base_url' => env('ZAZU_BASE_URL'),
        'inventory_path' => env('ZAZU_INVENTORY_PATH', '/api/external/inventario'),
        'agencies_path' => env('ZAZU_AGENCIES_PATH', '/api/external/sedes'),
        'token' => env('ZAZU_INVENTORY_TOKEN'),
        'timeout' => (int) env('ZAZU_TIMEOUT', 30),
        // Empresas de Zazu que vende la tienda (parte del nombre, separadas por coma). Las demás
        // (p. ej. BOX PRIME) no se sincronizan y sus productos ya guardados quedan con stock 0.
        // Con la variable vacía o ausente valen estas dos: una tienda sin lista vendería el inventario completo de Zazu.
        'companies' => array_values(array_filter(array_map('trim', explode(',', (string) env('ZAZU_COMPANIES', 'OVERSHARK,BRAVOS'))))) ?: ['OVERSHARK', 'BRAVOS'],
    ],

    // CRM-LIVEX-V2: recibe cada pedido pagado como venta (POST /api/v1/sales, clave API con permiso
    // sales.write). El CRM asigna la nota de venta NV-00014 (la misma serie que el resto de sus ventas y su
    // agente IA), la envía a Zazu según el tipo de entrega, la registra en el ERP y emite la boleta
    // (App\Services\Crm\CrmSales). Solo se envía con CRM_SEND_SALES=true.
    'crm' => [
        'send_sales' => (bool) env('CRM_SEND_SALES', false),
        'base_url' => env('CRM_BASE_URL'),
        'api_key' => env('CRM_API_KEY'),
        'timeout' => (int) env('CRM_TIMEOUT', 30),
    ],


    'storefront' => [
        'live_url' => env('STOREFRONT_LIVE_URL'),
        'chat_url' => env('STOREFRONT_CHAT_URL'),
        // Envío gratis desde este subtotal (S/). 0 = sin envío gratis y sin barra en el header.
        'free_shipping_from' => (float) env('STOREFRONT_FREE_SHIPPING_FROM', 0),
        // Costo de envío (S/): a domicilio (solo Lima) y recojo en agencia Shalom. El almacén es gratis.
        'shipping_home' => (float) env('STOREFRONT_SHIPPING_HOME', 15),
        'shipping_agency' => (float) env('STOREFRONT_SHIPPING_AGENCY', 14),
        // Dirección y horario del almacén para "Recojo en almacén" (sin costo). Vacío = se coordina por WhatsApp.
        'pickup_address' => env('STOREFRONT_PICKUP_ADDRESS'),
        // Separo de los pedidos contraentrega (S/), pagado en el checkout con tarjeta (Izipay).
        // El recojo en almacén no lleva separo: se paga todo al recoger.
        'cod_deposit_home' => (float) env('STOREFRONT_COD_DEPOSIT_HOME', 15),
        'cod_deposit_agency' => (float) env('STOREFRONT_COD_DEPOSIT_AGENCY', 30),
    ],

];
