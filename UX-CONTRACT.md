# Overshark Commerce UX Contract

## Scope

This contract covers the `/admin/combos` CRUD surface and the public `/combos/{combo}` detail route.

The payment gateway settings at `/admin/pasarelas-pago` control encrypted provider credentials and checkout visibility for Culqi, Izipay, and Mercado Pago. Izipay uses the existing signed payment form; Mercado Pago uses a verified server notification to confirm an order. The browser return alone never marks a Mercado Pago order paid. Culqi is configurable but remains hidden until its checkout driver is integrated.

The product catalog at `/admin/productos` also follows the filter and brand-assignment behavior below.

The brand maintainer at `/admin/marcas` defines the public visual palette and storefront context for each commercial brand.

## Canonical behavior

| Operation | Trigger | Success | Failure | Recovery |
| --- | --- | --- | --- | --- |
| Create combo | `Guardar combo` | Return to combo list with success status | Inline form summary and field errors | Preserve entered values |
| Import catalog | `Cargar catálogo base` | Return to list with created/updated counts | Inline import warning | Sync ZAZU and rerun import |
| Toggle visibility | `Activar` / `Ocultar` | Return to list with status message | Preserve current card/list | Retry the same action |
| Delete combo | `Eliminar` then app dialog | Return to list with status message | Keep dialog context | Cancel or retry |
| Browse combo | `Ver página` | Dedicated public detail | 404 for inactive/missing combo | Return to home/catalog |

## Data rules

- Public combo selection resolves each unit to an available product variant with talla and color; the server revalidates the allowed variant and stock before adding it.
- Combo cart lines keep the selected variants for checkout display and use the configured combo `price`, never the individual product prices.

- `price` is the total combo price in Peruvian soles and is never inferred from product prices.
- Fixed combos store product names and quantities.
- Choice combos store eligible ZAZU styles plus a `selection_limit` and visible explanatory note.
- Unknown compositions remain empty and visible as pending; the importer does not invent products.
- A missing custom image uses the standard `default-hero-banner.png` asset.

## Product catalog

- The catalog supports URL-persisted filters for product name or SKU, company, and manually assigned brand.
- Assigning a brand from a product detail applies it to every synchronized variant of that product and does not get overwritten by the ZAZU inventory sync.
- Products without a brand remain visible as `Sin asignar` and are recoverable through the product detail screen.

## Brand storefronts

- Each active brand has a stable public route at `/marcas/{slug}` and is reachable from the public `Marcas` menu.
- A brand storefront uses that brand's configured primary, secondary, accent, background, and text colors while preserving the shared product-card behavior.
- Brand storefront queries match `productos.marca` case-insensitively and show only products assigned to the selected brand with price and stock available.
- Hiding a brand removes it from the public menu and makes its storefront unavailable without deleting its products or palette configuration.

## Accessibility and resilience

- Forms use `novalidate`, real labels, server-side validation, and preserved values after errors.
- Delete uses an app-owned dialog; browser confirmation APIs are not used.
- The list is a bounded card grid, with all actions available by keyboard and touch.
- Import can create the catalog before ZAZU products are linked; the unresolved names are reported inline.

## Payment gateways

- Saving a blank credential preserves the stored value; private credentials are encrypted at rest and never rendered back into the form.
- Enabling a gateway requires the credentials used by its payment flow. Only configured gateways with an implemented checkout driver are offered to buyers.
- Mercado Pago order confirmation validates the webhook signature, retrieves the payment from Mercado Pago, and checks the order reference, PEN currency, and expected amount before idempotent confirmation.
- Mercado Pago needs only the Access Token to be enabled; saving or "Probar conexión" checks it against Mercado Pago and rejects test tokens in production or accounts outside Peru. The webhook secret is recommended: when stored, unsigned or badly signed notifications are rejected.
- Returning from Mercado Pago, or pressing "Ya pagué", looks the payment up in the API; URL parameters never confirm an order on their own. A pending Mercado Pago order can reopen its checkout until the reservation expires, and the preference expires with it.
- Izipay keys stored in `.env` keep counting as configured when the panel has none.
- Existing manual payment methods remain separate from online gateway configuration.
- Izipay credential keys map to the existing REST V4 payment form and signed callback; Mercado Pago can be enabled independently.
