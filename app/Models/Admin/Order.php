<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'number_zazu', 'productos_json', 'precio_total', 'metodo_pago_id',
        'cliente_id', 'cliente_name', 'cliente_cel', 'cliente_doc_identidad',
        'tipo_documento_id', 'status_order_id', 'tipo_envio_id',
        'tipo_registro_id', 'distrito_id', 'direccion', 'cuenta_cliente',
        'email', 'subtotal', 'envio', 'entrega', 'agencia', 'referencia', 'tipo_comprobante', 'ruc', 'razon_social',
        'metodo_pago_nombre', 'comprobante_pago', 'checkout_token', 'separo', 'pago_monto', 'pago_referencia', 'payment_gateway',
        'fraud_alert_at', 'fraud_alert_detail', 'fraud_alert_reviewed_at',
        'pago_qr', 'pago_qr_expira', 'agencia_shalom_id', 'nota_venta', 'crm_sale_id', 'fecha_envio', 'customer_account_id',
    ];

    protected function casts(): array
    {
        return [
            'productos_json' => 'array',
            'precio_total' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'envio' => 'decimal:2',
            'separo' => 'decimal:2',
            'pago_monto' => 'decimal:2',
            'pago_qr_expira' => 'datetime',
            'fecha_envio' => 'date',
            'fraud_alert_at' => 'datetime',
            'fraud_alert_reviewed_at' => 'datetime',
        ];
    }

    /** Pedido contraentrega (con o sin separo). */
    public function isCashOnDelivery(): bool
    {
        return str_starts_with((string) $this->metodo_pago_nombre, \App\Support\Checkout::COD_NAME);
    }

    public function statusName(): ?string
    {
        return $this->statusOrder?->name;
    }

    /** Número que ve la clienta y el equipo: la nota de venta del CRM (NV-00006) o, mientras no la tenga, OS-000012. */
    public function displayNumber(): string
    {
        return $this->nota_venta ?: (string) $this->number_zazu;
    }

    /** Código interno del pedido web (OS-000012): la referencia con la que el CRM reconoce la venta. */
    public function code(): string
    {
        return 'OS-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function maskedEmail(): string
    {
        [$local, $domain] = array_pad(explode('@', (string) $this->email, 2), 2, '');

        return mb_substr($local, 0, 3).'•••@'.$domain;
    }

    /** Alerta de fraude de Mercado Pago que el equipo aún no revisó: el pedido no se debe despachar. */
    public function hasOpenFraudAlert(): bool
    {
        return $this->fraud_alert_at !== null && $this->fraud_alert_reviewed_at === null;
    }

    public function scopeWithOpenFraudAlert($query)
    {
        return $query->whereNotNull('fraud_alert_at')->whereNull('fraud_alert_reviewed_at');
    }

    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function metodoPago() { return $this->belongsTo(MetodoPago::class); }
    public function statusOrder() { return $this->belongsTo(StatusOrder::class); }
    public function tipoEnvio() { return $this->belongsTo(TipoEnvio::class); }
    public function tipoRegistro() { return $this->belongsTo(TipoRegistro::class); }
    public function tipoDocumento() { return $this->belongsTo(TipoDocumento::class); }
    public function distrito() { return $this->belongsTo(Distrito::class); }
    public function tasks() { return $this->hasMany(OrderTask::class); }
}
