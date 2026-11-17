# API de integraciones de Overshark

Para sistemas externos (ERP, courier, marketplace, catálogos de anuncios) que leen el catálogo y los pedidos de la tienda,
actualizan el estado de los pedidos o reciben avisos automáticos. Las claves y los webhooks se administran en
**Admin → Configuración → Integraciones**.

## Autenticación

- URL base: `https://overshark.com.pe/api/v1`
- Cada solicitud lleva la clave: `Authorization: Bearer osk_…` (o el encabezado `X-Api-Key: osk_…`).
- La clave se ve completa una sola vez, al crearla. Si se pierde o se filtra, revócala y crea otra.
- Permisos por clave: `catalog.read`, `orders.read`, `orders.write`.
- Límite: 120 solicitudes por minuto por clave (luego `429`).
- Respuestas en JSON. Errores: `401` clave inválida o revocada, `403` sin permiso, `404` no existe,
  `409` estado no permitido, `422` datos inválidos (`errors` indica el campo).

## Endpoints

| Método y ruta | Permiso | Uso |
|---|---|---|
| `GET /ping` | cualquiera | Prueba la clave; devuelve su nombre y permisos. |
| `GET /products` | `catalog.read` | Catálogo: productos con sus variantes (color, talla, SKU, precio, stock, imagen). |
| `GET /products/{id}` | `catalog.read` | Un producto con todas sus variantes. |
| `GET /orders` | `orders.read` | Pedidos confirmados (pagados, separo pagado, contraentrega, enviados, entregados). |
| `GET /orders/{id}` | `orders.read` | Un pedido. |
| `PATCH /orders/{id}/status` | `orders.write` | Cambia el estado a `Enviado`, `Entregado` o `Cancelado`. |

### Parámetros

- `GET /products`: `q` (nombre), `updated_since` (fecha ISO 8601, p. ej. `2026-11-17T10:00:00-05:00`
  codificada en la URL; devuelve las variantes cambiadas desde entonces, útil para sincronizar stock), `in_stock=1`,
  `page`, `per_page` (máx. 100, por defecto 50).
- `GET /orders`: `status` (un estado exacto, `all` para incluir los que esperan pago y los anulados), `from` y `to`
  (fechas de creación), `updated_since`, `page`, `per_page`.
- `PATCH /orders/{id}/status`: cuerpo JSON `{"status": "Enviado"}`. También acepta `shipped`, `delivered` y `cancelled`.
  Solo se cambian pedidos confirmados; los estados de pago los pone únicamente la tienda (`409` si no).

Las listas traen `meta: {page, per_page, total, last_page}`.

### Ejemplo

```bash
curl https://overshark.com.pe/api/v1/orders?per_page=20 \
  -H "Authorization: Bearer osk_…"

curl -X PATCH https://overshark.com.pe/api/v1/orders/123/status \
  -H "Authorization: Bearer osk_…" -H "Content-Type: application/json" \
  -d '{"status": "Enviado"}'
```

### Pedido (formato de `GET /orders/{id}` y de los webhooks)

```json
{
  "id": 123, "number": "NV-00014", "web_number": "WEB-ABC123", "code": "OS-000123", "sales_note": "NV-00014",
  "status": "Pago confirmado", "created_at": "…", "updated_at": "…",
  "customer": {"name": "…", "email": "…", "phone": "…", "document_type": "DNI", "document_number": "…"},
  "delivery": {"type": "domicilio", "address": "…", "reference": "…", "district": "…", "province": "…",
               "department": "…", "agency": null, "shipping_date": "2026-11-18"},
  "invoice": {"type": "boleta", "ruc": null, "business_name": null},
  "payment": {"method": "…", "gateway": "mercadopago", "reference": "…", "amount_paid": 95.0, "cash_on_delivery_deposit": null},
  "totals": {"currency": "PEN", "subtotal": 80.0, "shipping": 15.0, "total": 95.0},
  "items": [{"type": "producto", "name": "CLASICO", "variant_id": 7, "combo_id": null, "color": "NEGRO",
             "size": "M", "quantity": 2, "unit_price": 40.0, "selections": []}],
  "fraud_alert": false
}
```

## Webhooks

Overshark envía un `POST` JSON a la URL registrada cuando ocurre un evento al que se suscribió:

| Evento | Cuándo |
|---|---|
| `order.confirmed` | El pedido quedó pagado (o el separo) o se aceptó como contraentrega: listo para preparar. |
| `order.shipped` | El pedido pasó a Enviado (panel o API). |
| `order.delivered` | El pedido pasó a Entregado. |
| `order.cancelled` | El pedido se anuló (panel o API). |
| `ping` | Botón «Enviar prueba» del panel. |

Cuerpo: `{"id": "<uuid del aviso>", "event": "order.confirmed", "created_at": "…", "data": { …pedido… }}`.

Encabezados: `X-Overshark-Event`, `X-Overshark-Delivery` (id del aviso, para descartar repetidos) y
`X-Overshark-Signature: sha256=<HMAC-SHA256 del cuerpo con el secreto del webhook>`.

Validar la firma (PHP):

```php
$body = file_get_contents('php://input');
$expected = 'sha256='.hash_hmac('sha256', $body, $secretoDelWebhook);
if (! hash_equals($expected, $_SERVER['HTTP_X_OVERSHARK_SIGNATURE'] ?? '')) {
    http_response_code(401);
    exit;
}
```

Responde `2xx` en menos de 10 segundos. Si no, el aviso se reintenta hasta 5 veces (1 min, 5 min, 30 min, 2 h y 6 h).
Un mismo aviso puede llegar más de una vez: usa `X-Overshark-Delivery` o el `id` para no procesarlo dos veces.
