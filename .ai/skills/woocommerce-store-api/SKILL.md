---
name: woocommerce-store-api
description: Add or modify routes in the WooCommerce Store API (`/wc/store/v1/*`). Use when creating new Store API endpoints, modifying existing ones, or designing the schemas blocks and external integrations consume. Covers authentication, REST URL design, schema/response alignment, variation handling, idempotency, and common pitfalls.
---

# WooCommerce Store API

The Store API is the public REST surface used by Woo blocks (Cart, Checkout, Mini-Cart) and external integrations. It lives under `/wc/store/v1/*` and has its own conventions distinct from the older `/wc/v3/*` admin REST API.

## When to use this skill

- Adding a new route under `/wc/store/v1/`.
- Modifying an existing route's response shape, status codes, or argument schema.
- Designing the schema for a new resource the frontend will consume.
- Wiring a block (or iAPI store) to a Store API endpoint.

This skill complements `woocommerce-backend-dev` (general PHP conventions) and `woocommerce-performance` (cache priming patterns). Read those first for general conventions; this skill covers Store-API-specific decisions.

## Topics

- [authentication.md](authentication.md) — `permission_callback` styles, nonce enforcement via `AbstractCartRoute`, when to extend which abstract.
- [rest-conventions.md](rest-conventions.md) — Path/body/query separation, collection vs item vs action routes, status codes, idempotency.
- [schema-design.md](schema-design.md) — Schema as public contract, field discipline, response-shape alignment.
- [variation-handling.md](variation-handling.md) — Server-authoritative variation reconciliation.
- [performance.md](performance.md) — Where to apply cache priming in Store API responses. Cross-links to `woocommerce-performance` for the underlying patterns.

## Key principles

- **Schemas are the public contract.** What the schema declares must be what the route returns. Don't bolt fields onto a response after the schema produced it.
- **Pick the right abstract.** Routes that mutate per-user state via cookies must extend `AbstractCartRoute` (which enforces nonces) or implement equivalent CSRF protection. `AbstractRoute` alone is for read-only or own-auth routes.
- **GET is safe.** No side effects, no body. Auto-create-on-read is allowed only as in-memory materialisation; persist on the first explicit write.
- **Server is authoritative on identity.** Don't trust client-supplied attribute payloads for variations; derive them from the variation product.
- **Don't ship dead fields.** Schema properties that have only one possible value forever, or that mirror data already exposed elsewhere, are public-API surface that's easier to add later than to retract.

## Reference files

- [Authentication.php](../../../plugins/woocommerce/src/StoreApi/Authentication.php) — global Store API auth filter; deliberately bypasses WP's cookie-nonce check.
- [AbstractCartRoute.php](../../../plugins/woocommerce/src/StoreApi/Routes/V1/AbstractCartRoute.php) — nonce enforcement, cart-session loading, `Nonce`/`Cart-Token` response headers.
- [AbstractRoute.php](../../../plugins/woocommerce/src/StoreApi/Routes/V1/AbstractRoute.php) — base class for routes that don't need cart-session/nonce machinery.
- [AbstractSchema.php](../../../plugins/woocommerce/src/StoreApi/Schemas/V1/AbstractSchema.php) — `prepare_html_response()`, `prepare_money_response()`, response-formatting helpers.
- [CartItems.php](../../../plugins/woocommerce/src/StoreApi/Routes/V1/CartItems.php) — canonical example of collection-style POST/DELETE routes.
- [CartController.php](../../../plugins/woocommerce/src/StoreApi/Utilities/CartController.php) — variation reconciliation reference (`parse_variation_data()`).
