<?php

namespace App\Providers;

use App\Models\Admin\PaymentGateway;
use Illuminate\Support\ServiceProvider;

class PaymentGatewayConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! app()->runningInConsole() || ! $this->app->runningUnitTests()) {
            try {
                $rows = PaymentGateway::query()->get()->keyBy('provider');
                foreach (PaymentGateway::PROVIDERS as $provider) {
                    $row = $rows->get($provider);
                    config(['payments.gateways.'.$provider => array_merge($row?->credentials ?? [], [
                        'enabled' => (bool) $row?->enabled,
                        'environment' => $row?->environment ?? 'test',
                    ])]);
                }
                $izipay = $rows->get('izipay');
                if ($izipay) {
                    config([
                        'services.izipay.shop_id' => $izipay->credential('shop_id', config('services.izipay.shop_id')),
                        'services.izipay.password' => $izipay->credential('password', config('services.izipay.password')),
                        'services.izipay.public_key' => $izipay->credential('public_key', config('services.izipay.public_key')),
                        'services.izipay.hmac_key' => $izipay->credential('hmac_key', config('services.izipay.hmac_key')),
                    ]);
                }
            } catch (\Throwable) {
                // Artisan commands and first install may run before the database migration exists.
            }
        }
    }
}
