<?php

return [
    'secret' => env('JWT_SECRET'),
    'issuer' => env('JWT_ISSUER', env('APP_URL', 'http://localhost')),
    'ttl' => (int) env('JWT_TTL', 60),
    'cookie' => env('JWT_COOKIE', 'overshark_admin_token'),
    'secure_cookie' => (bool) env('JWT_SECURE_COOKIE', env('APP_ENV') === 'production'),
];
