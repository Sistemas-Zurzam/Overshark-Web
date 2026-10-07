---
version: alpha
name: "Overshark Commerce"
description: "Tienda de ropa urbana con una capa administrativa directa, azul eléctrico y superficies blancas de alta legibilidad."
colors:
  primary: "#111111"
  accent: "#0078D7"
  surface: "#FFFFFF"
  canvas: "#F1F2F4"
  line: "#A2A2A2"
  danger: "#DC2626"
  success: "#047857"
typography:
  sans:
    fontFamily: "SF Pro Display, SF Pro Text, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
  mono:
    fontFamily: "ui-monospace, SFMono-Regular, Menlo, monospace"
rounded:
  DEFAULT: "0.5rem"
  sm: "0.5rem"
  md: "0.75rem"
  lg: "1rem"
spacing:
  section-gap: "3rem"
  page-max: "90rem"
components:
  button:
    primary: "#111111 with #0078D7 hover"
    radius: "0.5rem"
  card:
    surface: "#FFFFFF"
    border: "#A2A2A2"
    radius: "1rem"
  admin-form:
    surface: "#FFFFFF"
    input-surface: "#F1F2F4"
    radius: "1rem"
---

# Overshark Commerce Design System

## Overview

### Creative North Star

Overshark borrows from a crisp product-tag wall: black labels, electric-blue signal color, and generous white space around the merchandise. The admin surface is operational and dense enough to scan, while the public surface keeps the same sharp black/blue identity with stronger imagery.

### Product context and register

- **Audience and primary job:** Administrators create and maintain product promotions; shoppers discover a combo and continue to the included products.
- **Target market(s) and evidence:** Peruvian commerce evidence is present in the S/ currency and ZAZU inventory integration.
- **Locale(s) and language policy:** Spanish UI and Spanish validation copy; prices use `S/` with two decimals.
- **Usage scene:** Desktop-first admin work with mobile-safe forms and public browsing.
- **Register:** Hybrid: product utility in `/admin`; energetic commerce expression on public pages.
- **Memorable signature:** The electric-blue “Combos” trigger and image-led promo cards.
- **Restraint:** Forms, prices, states, and destructive actions stay quiet and explicit.
- **Anti-references:** Avoid generic SaaS purple gradients, glassmorphism, and decorative dashboards that hide price or inventory meaning.
- **Token ownership/runtime mapping:** `resources/css/app.css` is the runtime source; this file documents its existing tokens and does not generate a second theme.

## Colors

Black is the primary action and title color. `#0078D7` is reserved for active navigation, links, focus accents, and combo emphasis. `#F1F2F4` is the canvas/input surface; white is the working surface. Red is only for destructive actions, and green/amber communicate status and pending product linkage with text as well as color.

## Typography

The existing SF Pro Display/Text stack remains canonical. Heavy weights carry page titles, prices, and combo names; normal weights carry descriptions and product metadata. Uppercase is limited to eyebrow labels and promotional names, not long explanations.

## Layout

Admin pages use a wide max-width with rounded white working panels and responsive card grids. Long forms remain in document flow. The combo catalog uses cards because each record is a promotional object; product and price details stay visible without hover-only behavior.

## Elevation & Depth

Hierarchy comes from white-on-gray surfaces, thin gray borders, and restrained shadows. Promo imagery may carry a stronger shadow; admin controls do not use floating decorative layers.

## Shapes

Controls use 0.5rem rounding, cards use 1rem, and public promo surfaces use larger 1.5rem rounding where imagery benefits from a softer frame. Destructive controls remain outlined in normal state.

## Components

### Foundational visual states

Interactive controls have explicit hover, visible focus, busy/disabled, and error states. Empty catalog states explain the next action. Import warnings remain inline because missing ZAZU product links need follow-up.

### Buttons and actions

The primary action is solid black with blue hover. Import and secondary actions are outlined. Delete is red and separated from activation/toggle actions.

### Navigation and data display

Combo cards show brand, modality, price, quantity, composition, and status together. Public combo cards link to a dedicated detail page, not a dead placeholder route.

### Forms and overlays

Combo creation uses labeled native inputs/selects, a repeatable product-and-quantity row, server validation, and an app-owned delete dialog. The form uses the shared document scroll rather than a nested fixed-height panel.

### Motion

Existing hover and menu transitions remain. New form rows appear immediately; no animation is required for data correctness. Reduced-motion users retain the same actions without decorative movement.

### Content and data visualization

Money is displayed as `S/ 99.00`; quantities use `×N`. “Composición pendiente” and “Productos aún no vinculados” are explicit states, never hidden blanks.

## Public storefront · Neón theme

The public storefront (`layouts/web.blade.php`, `body.theme-neon`) follows the Figma frame "Inicio · Neón · Hombre" (file *Overshark-Girls*). Admin keeps the light theme above; `.theme-neon` overrides only apply inside the storefront.

- **Tokens** (`@theme` in `resources/css/app.css`): `ink` #030A1C page, `panel` #0A1838 cards, `panel-2` #0F2148 raised/inputs, `edge` #1B3366 / `edge-strong` #2A4A8C borders, `neon` #2F6FD0 actions, `neon-soft` #5E9BEA accents/links, `mist` #B9CDE8 secondary text, `haze` #8299BF muted text, `frame` #E6EDF7 product photo frames, `live` #E5484D live-commerce red, `aqua` #2DD4BF "Outfit con IA". Use these utilities (`bg-panel`, `text-mist`, `border-edge`…) in storefront views, never `slate-*`/`white` surfaces.
- **Type:** headings stay heavy sans uppercase; the italic serif `font-display` (Playfair Display) is reserved for hero/editorial accents such as "hecho para *dominar*".
- **Buttons:** pills. `btn-primary` is the blue gradient, `btn-secondary` an outlined pill, `btn-light` the white hero CTA.
- **Hero:** banners from Admin › Banners render on the right side with a soft mask. Upload cut-out PNGs with transparent background to match Figma; photos with light backgrounds show as a glowing panel.
- **Live / chat / outfit cards:** links come from `STOREFRONT_LIVE_URL`, `STOREFRONT_CHAT_URL` and `STOREFRONT_OUTFIT_AI_URL`; without them the buttons jump to the footer contact.
- **HOMBRE / MUJER:** a first visit to `/` shows the "¿Qué quieres explorar?" selector (`web/explore.blade.php`, also at `/explorar`). Choosing a card, the header toggle or `?linea=hombre|mujer` stores the line in session and switches the hero copy. Products have no gender attribute yet, so it does not filter the catalog. The selector uses `font-condensed` (Bebas Neue) for its headings.

## Do's and Don'ts

- **Do:** keep combo price and composition visible in both admin and public detail.
- **Do:** preserve unresolved business composition as a visible note instead of guessing.
- **Don't:** represent a combo as only an image and URL.
- **Don't:** use browser `confirm()` or make the user infer why a combo has no linked products.
