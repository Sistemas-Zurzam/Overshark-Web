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
    ];

    protected function casts(): array
    {
        return [
            'productos_json' => 'array',
            'precio_total' => 'decimal:2',
        ];
    }

    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function metodoPago() { return $this->belongsTo(MetodoPago::class); }
    public function statusOrder() { return $this->belongsTo(StatusOrder::class); }
    public function tipoEnvio() { return $this->belongsTo(TipoEnvio::class); }
    public function tipoRegistro() { return $this->belongsTo(TipoRegistro::class); }
    public function tipoDocumento() { return $this->belongsTo(TipoDocumento::class); }
    public function distrito() { return $this->belongsTo(Distrito::class); }
}
