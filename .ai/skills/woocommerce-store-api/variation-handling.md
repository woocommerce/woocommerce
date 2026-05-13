# Variation attribute handling

Variable products and their variations are the most error-prone surface in the Store API. A request can take several shapes depending on the UX, the server has to reconcile the client's claim about which variation is being referenced, and the same logical variation can be expressed multiple ways. Get any of that wrong and you store ambiguous data, break idempotency, or return 500s on routine input.

This page documents the cart's handling so any new route that accepts variation references can mirror it.

## Why server-authoritative reconciliation matters

A typical Store API request looks like:

```json
{ "id": 99, "variation": { "attribute_pa_color": "blue" } }
```

Two things are claimed: variation 99 exists, and its colour is blue. **The first is verifiable; the second is not, unless you check.** A client (malicious or buggy) can send `id: 99` with `variation: { color: red }` while variation 99 is actually blue — and a route that trusts the client verbatim will store the wrong attributes.

This produces two downstream problems:

1. **Stored data lies.** The persisted row claims red but the variation product says blue. Reads return wrong data.
2. **Idempotency breaks.** If a route hashes the client's attribute payload to produce a storage key, two POSTs with different attribute orderings (or different values for the same logical variation) produce different keys — duplicate rows for the same item.

The fix is to derive canonical attributes from the variation product itself. Treat the client's payload as a hypothesis, not a source of truth.

## The two input shapes

WooCommerce front-end UX produces two shapes of variation input, both of which the cart accepts:

**Variation ID (resolved upstream).**

```json
{ "id": 99, "variation": { "attribute_pa_color": "blue" } }
```

The client has already picked a specific variation; `id` is its post ID. The `variation` array is a claim about its attributes that the server validates.

**Variable parent + attributes (resolved by the server).**

```json
{ "id": 42, "variation": [ {"attribute": "pa_color", "value": "blue"}, {"attribute": "pa_size", "value": "medium"} ] }
```

This is what the standard product page posts: `id` is the variable parent product, and the user's dropdown selections come along as the variation array. The server resolves to the matching variation via `WC_Data_Store::find_matching_product_variation()`.

Simple (non-variable) products have `variation = []` and skip the reconciliation entirely.

## The reconciliation flow

`CartController::parse_variation_data()` is the canonical pattern. The flow:

1. **Skip if the product isn't variable.** Simple products have no variation context — clear `variation` and return.
2. **Sanitise the posted variation array.** Map attribute names to the canonical `attribute_<slug>` form so all comparisons run against the same vocabulary.
3. **If the input is a variable parent ID, resolve it to a specific variation.** `get_variation_id_from_variation_data()` looks up which variation matches the posted attributes.
4. **With a variation ID in hand, validate posted attributes against the variation's expected attributes.** `wc_get_product_variation_attributes( $id )` returns the variation's expected slug for each variable attribute (or `''` for an "Any" slot).
5. **`ksort` the resulting variation array** so the same logical variation always serialises to the same key — important for idempotency keys hashed from this array.

## Specific values vs "Any" attributes

Each variable attribute on a variation is either a specific value (the variation pins `pa_color = blue`) or "Any" (the variation accepts any of the parent's allowed values for `pa_size`). They need different handling:

- **Specific-value attribute posted by the client** → must equal the expected slug, otherwise 400.
- **Specific-value attribute not posted** → the server fills in the expected value. The variation has a default; trust it.
- **"Any" attribute posted by the client** → must be in the parent's allowed slugs (`WC_Product_Attribute::get_slugs()`), otherwise 400.
- **"Any" attribute not posted** → 400 with "missing variation data". The server can't pick on the user's behalf — for "Color: blue, Size: any", the user has to choose a size.

`wc_get_product_variation_attributes()` returns slugs like `[ 'attribute_pa_color' => 'blue', 'attribute_pa_size' => '' ]`. The empty string is the marker for "Any".

## Slug canonicalisation

WooCommerce stores attribute values as **lowercase taxonomy term slugs**. Both sides of any comparison come from canonicalised storage, so use strict `===`/`!==`. Don't add case-insensitive matching — the cart doesn't, and consistency matters for predictable client behaviour.

Direct API clients (using `id` + `variation`) need to send slugs in their canonical form. The cart already canonicalises on its way into `cart_contents`, so server-side round-trips are clean.

## Throwing the right exception

Validation failures should throw `RouteException` directly with a 400 status. The Store API framework catches it and returns a structured error response — no wrapper or translation layer needed:

```php
throw new RouteException(
    'woocommerce_rest_invalid_variation_data',
    sprintf(
        /* translators: %1$s: Attribute name, %2$s: Allowed values. */
        esc_html__( 'Invalid value posted for %1$s. Allowed values: %2$s', 'woocommerce' ),
        esc_html( $attribute_label ),
        esc_html( implode( ', ', $attribute->get_slugs() ) )
    ),
    400
);
```

The cart uses two error codes for variation issues:

- `woocommerce_rest_invalid_variation_data` — a posted attribute has a value the variation doesn't accept. Surface the allowed values in the message.
- `woocommerce_rest_missing_variation_data` — an "Any" attribute wasn't posted. Surface the missing attribute label.

Both return 400. Don't throw a generic `\InvalidArgumentException` — exceptions that aren't `RouteException` fall through to the abstract route's generic handler, which returns a 500 with `woocommerce_rest_unknown_server_error` and obscures the real problem from the client.

## Test coverage

Any route that accepts variation references should have tests for:

1. **Variation ID input** → success, attributes validated against the variation.
2. **Variable parent + matching attributes** → success, server resolves to the correct variation.
3. **Variable parent + unmatchable attributes** → 400 with a meaningful error.
4. **Specific attribute defaulted by the server** — client sends nothing, server fills in correctly.
5. **"Any" attribute with a valid posted slug** → success.
6. **"Any" attribute with a slug not in the parent's allowed list** → 400 with allowed values listed.
7. **Mismatched specific-value posted by the client** → 400 with allowed values listed.
8. **Unposted "Any" attribute** → 400 with the missing attribute name.
9. **Simple product** → variation array clears to empty.

The variation path is where future regressions are most likely to land. Tests are the only durable lock on the reconciliation behaviour.

## Reference

- [`CartController::parse_variation_data()`](../../../plugins/woocommerce/src/StoreApi/Utilities/CartController.php) — the canonical reconciliation pattern; mirror this in any new route that accepts variation references.
- [`CartController::get_variation_id_from_variation_data()`](../../../plugins/woocommerce/src/StoreApi/Utilities/CartController.php) — resolves a variable parent + posted attributes to a specific variation ID.
- `wc_get_product_variation_attributes()` (WooCommerce core) — returns canonical slugs for a variation, with `''` for "Any" slots.
- `WC_Product_Attribute::get_slugs()` (WooCommerce core) — returns the allowed slug list for an attribute on the parent product.
- `WC_Data_Store::find_matching_product_variation()` (WooCommerce core) — underlying lookup used by `get_variation_id_from_variation_data()`.
