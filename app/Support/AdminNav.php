<?php

namespace App\Support;

use App\Models\Admin\Order;
use App\Models\Admin\Promocion;
use App\Models\ContactMessage;
use App\Models\LibroReclamacion;
use Illuminate\Support\Str;

/**
 * Datos del menú lateral del panel: marcas de lo pendiente en cada sección y qué sección está activa.
 */
class AdminNav
{
    /**
     * Cuántas cosas esperan atención en cada sección (solo las que tienen alguna), por nombre de ruta.
     *
     * @return array<string, array{count: int, tone: string, title: string}>
     */
    public static function badges(): array
    {
        $claims = LibroReclamacion::query()->whereNull('responded_at')->get();
        $overdue = $claims->filter(fn (LibroReclamacion $claim) => $claim->isOverdue())->count();

        $all = [
            'admin.pedidos.index' => [
                'count' => Order::query()->whereHas('statusOrder', fn ($query) => $query->whereIn('name', [Checkout::STATUS_PENDING, Checkout::STATUS_AWAITING_QR]))->count(),
                'tone' => 'warning', 'title' => 'pedidos por confirmar el pago',
            ],
            'admin.reclamos.index' => [
                'count' => $claims->count(),
                'tone' => $overdue > 0 ? 'danger' : 'warning',
                'title' => $overdue > 0 ? "reclamos sin responder ({$overdue} vencidos)" : 'reclamos sin responder',
            ],
            'admin.messages.index' => [
                'count' => ContactMessage::query()->whereNull('read_at')->count(),
                'tone' => 'info', 'title' => 'mensajes sin leer',
            ],
            'admin.promociones.index' => [
                'count' => Promocion::query()->live()->count(),
                'tone' => 'success', 'title' => 'promociones activas ahora',
            ],
        ];

        return array_filter($all, fn (array $badge) => $badge['count'] > 0);
    }

    /** ¿Esta entrada del menú es la sección actual? También lo es mientras estás en sus detalles (un pedido, editar un combo…). */
    public static function isActive(string $routeName): bool
    {
        if ($routeName === 'admin.dashboard') {
            return request()->routeIs($routeName);
        }

        return request()->routeIs(Str::beforeLast($routeName, '.').'.*');
    }
}
