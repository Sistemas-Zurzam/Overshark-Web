<?php

use App\Providers\AppServiceProvider;
use App\Providers\IntegrationsServiceProvider;
use App\Providers\PaymentGatewayConfigServiceProvider;

return [
    AppServiceProvider::class,
    PaymentGatewayConfigServiceProvider::class,
    IntegrationsServiceProvider::class,
];
