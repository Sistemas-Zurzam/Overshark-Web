# Overshark Commerce UX Contract

## Scope

This contract covers the `/admin/combos` CRUD surface and the public `/combos/{combo}` detail route.

## Canonical behavior

| Operation | Trigger | Success | Failure | Recovery |
| --- | --- | --- | --- | --- |
| Create combo | `Guardar combo` | Return to combo list with success status | Inline form summary and field errors | Preserve entered values |
| Import catalog | `Cargar catálogo base` | Return to list with created/updated counts | Inline import warning | Sync ZAZU and rerun import |
| Toggle visibility | `Activar` / `Ocultar` | Return to list with status message | Preserve current card/list | Retry the same action |
| Delete combo | `Eliminar` then app dialog | Return to list with status message | Keep dialog context | Cancel or retry |
| Browse combo | `Ver página` | Dedicated public detail | 404 for inactive/missing combo | Return to home/catalog |

## Data rules

- `price` is the total combo price in Peruvian soles and is never inferred from product prices.
- Fixed combos store product names and quantities.
- Choice combos store eligible ZAZU styles plus a `selection_limit` and visible explanatory note.
- Unknown compositions remain empty and visible as pending; the importer does not invent products.
- A missing custom image uses the standard `default-hero-banner.png` asset.

## Accessibility and resilience

- Forms use `novalidate`, real labels, server-side validation, and preserved values after errors.
- Delete uses an app-owned dialog; browser confirmation APIs are not used.
- The list is a bounded card grid, with all actions available by keyboard and touch.
- Import can create the catalog before ZAZU products are linked; the unresolved names are reported inline.
