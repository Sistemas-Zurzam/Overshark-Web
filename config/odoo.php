<?php

return [
    'base_url' => rtrim(env('ODOO_BASE_URL', ''), '/'),
    'database' => env('ODOO_DATABASE', ''),
    'user' => env('ODOO_USER', ''),
    'api_key' => env('ODOO_API_KEY', ''),
    'timeout' => (int) env('ODOO_TIMEOUT', 60),
    'products_limit' => (int) env('ODOO_PRODUCTS_LIMIT', 200),
    'products_warehouse_code' => env('ODOO_PRODUCTS_WAREHOUSE_CODE'),
    'products_warehouse_name' => env('ODOO_PRODUCTS_WAREHOUSE_NAME'),
    'products_company_id' => env('ODOO_PRODUCTS_COMPANY_ID') ? (int) env('ODOO_PRODUCTS_COMPANY_ID') : null,
    'pos_session_id' => env('ODOO_POS_SESSION_ID') ? (int) env('ODOO_POS_SESSION_ID') : null,
];
