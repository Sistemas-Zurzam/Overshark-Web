<?php

namespace App\Support;

/**
 * Documentos legales de la tienda (Figma "10 · Legal / Términos · Privacidad · Reclamaciones"): cada uno se muestra
 * como página (/terminos-y-condiciones, /politica-de-privacidad) y en un modal desde el checkout y los footers.
 * Texto de trabajo del diseño: los datos del proveedor siguen "por confirmar" hasta la validación legal.
 */
class LegalDocuments
{
    /** Datos del proveedor: clave en SiteContent => etiqueta que se muestra. */
    public const PROVIDER_FIELDS = ['razon_social' => 'Razón social', 'ruc' => 'RUC', 'domicilio' => 'Domicilio', 'canal' => 'Canal de atención'];

    public const DRAFT_NOTE = 'Versión de trabajo · pendiente de validación legal y datos del operador.';

    /**
     * Documentos con los textos del panel (Admin → Contenido de la tienda → Documentos legales) encima de los de trabajo.
     *
     * @return array<string, array{title: string, route: string, description: string, sections: array<int, array{0: string, 1: string}>}>
     */
    public static function all(): array
    {
        $saved = SiteContent::saved()['legal'] ?? [];

        return collect(self::defaults())->map(function (array $doc, string $key) use ($saved): array {
            foreach ($doc['sections'] as $index => [$heading, $text]) {
                $edited = $saved[$key][$index] ?? [];
                $doc['sections'][$index] = [
                    filled($edited['h'] ?? null) ? $edited['h'] : $heading,
                    filled($edited['t'] ?? null) ? $edited['t'] : $text,
                ];
            }

            return $doc;
        })->all();
    }

    /** Nota al pie de los documentos (null si se ocultó en el panel). */
    public static function note(): ?string
    {
        return SiteContent::get('legal.hide_note') ? null : SiteContent::text('legal.note');
    }

    /** Textos de trabajo de cada documento, tal como vienen del diseño. */
    public static function defaults(): array
    {
        return [
            'terms' => [
                'title' => 'Términos y condiciones',
                'route' => 'web.terms',
                'description' => 'Condiciones de compra de la tienda Overshark: productos, precios, pago, entrega, cambios y reclamaciones.',
                'sections' => [
                    ['1. Identificación del proveedor', 'Esta tienda corresponde a Overshark Hombre. El proveedor responsable debe identificarse con su razón social, RUC, domicilio y canal de atención en el bloque de información legal. Revisa estos datos antes de realizar una compra.'],
                    ['2. Productos y disponibilidad', 'Cada ficha presenta las características, composición, talla, color y precio del producto. Selecciona las variantes antes de agregarlo al carrito. La disponibilidad debe confirmarse al procesar el pedido; si un producto no puede atenderse, se informarán las alternativas y la devolución que corresponda.'],
                    ['3. Precios y promociones', 'Los importes se expresan en soles peruanos (PEN). El resumen del pedido debe mostrar el precio final, los impuestos aplicables, los descuentos y el costo de envío antes del pago. Cada combo indica su cantidad de prendas, productos elegibles y condiciones. Los beneficios se aplican únicamente a las promociones identificadas para esta marca.'],
                    ['4. Pedido y pago', 'Revisa las prendas, cantidades, tallas, colores y datos de entrega antes de confirmar. Los medios disponibles se muestran en el checkout. La confirmación del pedido debe indicar su número y el estado del pago. Una operación pendiente o rechazada no debe presentarse como pagada.'],
                    ['5. Entrega', 'El costo, la cobertura, el plazo estimado y la modalidad de envío se informan antes de finalizar la compra. Proporciona datos completos y un contacto para coordinar la entrega. Cualquier incidencia o cambio debe comunicarse por el canal de atención.'],
                    ['6. Cambios, devoluciones e incidencias', 'Si recibes un producto con una falla, diferente al pedido o incompleto, contacta al proveedor para evaluar la solución que corresponda. Las condiciones comerciales de cambios por talla o preferencia deben informarse antes de la compra y no limitan los derechos reconocidos por la normativa aplicable. El plazo y procedimiento comercial están pendientes de validación.'],
                    ['7. Atención y reclamaciones', 'Puedes consultar sobre tu pedido mediante Contacto o presentar un reclamo o queja en el Libro de Reclamaciones virtual. No necesitas crear una cuenta para acceder al Libro. Su uso no impide acudir a otras vías de solución.'],
                    ['8. Vigencia de las condiciones', 'Las actualizaciones deben mostrar su fecha de publicación. Las condiciones informadas al confirmar una compra son la referencia para ese pedido. Este texto es un borrador pendiente de validación del proveedor.'],
                ],
            ],
            'privacy' => [
                'title' => 'Política de privacidad',
                'route' => 'web.privacy',
                'description' => 'Cómo Overshark trata tus datos personales: qué solicitamos, para qué, con quién se comparten y cómo ejercer tus derechos.',
                'sections' => [
                    ['1. Responsable del tratamiento', 'El responsable que opera Overshark Hombre debe identificarse con su razón social, domicilio y canal para ejercer derechos. Estos datos están pendientes de confirmación y deben completarse antes de publicar esta política.'],
                    ['2. Datos que solicitamos', 'Según el trámite, podemos solicitar datos de identificación, contacto, entrega, productos seleccionados, información del pedido y comunicaciones de atención. El Libro solicita los datos necesarios para identificar al consumidor y atender su reclamo o queja. Los campos obligatorios y opcionales se distinguen en cada formulario.'],
                    ['3. Finalidades', 'Los datos se utilizan para gestionar pedidos, pagos y entregas; responder consultas; atender incidencias y reclamaciones; y cumplir las obligaciones aplicables. Si falta información necesaria, se comunicará qué gestión no puede completarse. La base jurídica de cada finalidad debe quedar validada por el responsable.'],
                    ['4. Comunicaciones comerciales', 'El envío de publicidad requiere una elección independiente cuando corresponda. Comprar o presentar un reclamo no debe obligarte a aceptar publicidad. Podrás retirar una autorización comercial por el canal que el responsable habilite.'],
                    ['5. Encargados y destinatarios', 'La operación puede requerir servicios de pago, logística y soporte que accedan a datos necesarios para su función. Antes de publicar, el responsable debe confirmar los proveedores, destinatarios y posibles transferencias internacionales que efectivamente utilice. No se declara una transferencia sin verificarla.'],
                    ['6. Conservación y seguridad', 'Los datos deben conservarse durante el tiempo necesario para sus finalidades y las obligaciones aplicables. El responsable debe definir y validar los plazos por categoría de información. Evita enviar claves, códigos de seguridad o datos completos de tarjetas por los formularios de contacto o reclamaciones.'],
                    ['7. Tus derechos', 'Puedes solicitar acceso, rectificación, cancelación y oposición según la normativa peruana de protección de datos. El canal de atención de derechos y el procedimiento de identificación deben completarse antes de publicar. También puedes acudir a la Autoridad Nacional de Protección de Datos Personales.'],
                    ['8. Cookies y actualizaciones', 'El inventario de cookies, sus finalidades y las opciones de elección deben corresponder a las tecnologías realmente utilizadas. Este contenido y la identificación del banco de datos están pendientes de revisión. Cualquier actualización debe indicar su fecha de publicación.'],
                ],
            ],
        ];
    }

    public static function find(string $key): array
    {
        return self::all()[$key] ?? abort(404);
    }
}
