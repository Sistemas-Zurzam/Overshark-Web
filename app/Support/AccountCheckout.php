<?php

namespace App\Support;

use App\Models\CustomerAccount;
use App\Models\CustomerAddress;

/**
 * Puente entre la cuenta del comprador y el checkout. Solo precarga el formulario (la clienta puede cambiar cualquier
 * dato en el pedido sin tocar su perfil) y guarda una dirección cuando ella lo pide explícitamente.
 * La cuenta siempre sale del servidor (guard "customer"), nunca de un dato enviado por el navegador.
 */
class AccountCheckout
{
    /** Datos de contacto de la cuenta para el primer paso del checkout. */
    public static function contact(?CustomerAccount $account): array
    {
        if (! $account) {
            return [];
        }

        return array_filter([
            'email' => $account->email,
            'nombres' => $account->name,
            'apellidos' => $account->apellidos,
            'phone' => $account->phone,
        ], fn ($value) => filled($value));
    }

    /** Aplica una dirección guardada (delivery a domicilio) sobre los datos del checkout. */
    public static function withAddress(array $customer, CustomerAddress $address): array
    {
        $customer = array_merge($customer, array_filter([
            'delivery' => 'domicilio',
            'distrito_id' => $address->distrito_id,
            'address' => $address->address,
            'reference' => $address->reference,
            'delivery_lat' => $address->lat,
            'delivery_lng' => $address->lng,
        ], fn ($value) => $value !== null && $value !== ''));

        // La dirección guarda quién recibe; el celular de la cuenta solo se usa si el formulario no tiene uno.
        if (blank($customer['phone'] ?? null)) {
            $customer['phone'] = $address->phone;
        }

        return $customer;
    }

    /** Guarda en la libreta la dirección usada en el pedido, si no existe ya una igual. */
    public static function saveAddress(CustomerAccount $account, array $customer, ?string $alias): void
    {
        if (($customer['delivery'] ?? null) !== 'domicilio') {
            return;
        }

        $exists = $account->addresses()
            ->where('distrito_id', $customer['distrito_id'])
            ->whereRaw('LOWER(TRIM(address)) = ?', [mb_strtolower(trim($customer['address']))])
            ->exists();

        if ($exists) {
            return;
        }

        $account->addresses()->create([
            'alias' => in_array($alias, ['Casa', 'Trabajo', 'Otro'], true) ? $alias : 'Casa',
            'recipient' => trim($customer['nombres'].' '.$customer['apellidos']),
            'phone' => $customer['phone'],
            'distrito_id' => $customer['distrito_id'],
            'address' => $customer['address'],
            'reference' => $customer['reference'] ?? null,
            'lat' => $customer['delivery_lat'] ?? null,
            'lng' => $customer['delivery_lng'] ?? null,
            'is_default' => ! $account->addresses()->exists(),
        ]);
    }
}
