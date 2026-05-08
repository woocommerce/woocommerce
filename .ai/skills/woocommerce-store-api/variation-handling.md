# Variation attribute handling

Variable products and their variations are the single most error-prone surface in any Store-API-adjacent feature. The patterns below are specific to how WooCommerce models variations and how the cart already handles them — mirror these in any new route that accepts variation references.

## The problem: client-supplied attributes can lie

A typical Store API request might look like:

```json
{ "variation_id": 99, "variation": { "attribute_pa_color": "blue" } }
```

Two things are claimed: variation 99 exists, and its colour is blue. **The first is verifiable; the second is not, unless you check.** A client (malicious or buggy) can send `variation_id: 99` with `variation: { color: red }` while variation 99 is actually red — and a route that trusts the client verbatim will store the wrong attributes.

This produces two downstream problems:

1. **Stored data lies.** The persisted row claims red but the variation product says blue. Reads return wrong data.
2. **Idempotency breaks.** If a route hashes the client's attribute payload to produce a storage key, two POSTs with different attribute orderings (or different values for the same logical variation) produce different keys — duplicate rows for the same item.

## The fix: server-authoritative reconciliation

Mirror `CartController::parse_variation_data()`. For any route that accepts a `variation_id`, derive the canonical attributes from the variation product itself. The client's payload is either ignored (for specific-value attributes) or validated against the parent's allowed values (for "any" attributes).

```php
public static function from_product(
    int $product_or_variation_id,
    array $variation = array(),
    int $quantity = 1
): ?self {
    $product = wc_get_product( $product_or_variation_id );
    if ( ! $product ) {
        return null;
    }

    if ( $product->is_type( ProductType::VARIATION ) ) {
        $variation_id = $product->get_id();
        $product_id   = $product->get_parent_id();
        $variation    = self::resolve_variation_attributes( $product, $variation );
    } elseif ( $product->is_type( ProductType::VARIABLE ) ) {
        // Variable parent passed instead of a variation — explicit error.
        throw new \InvalidArgumentException(
            esc_html__( 'When saving a variation, product_id must be the variation ID, not the parent product ID.', 'woocommerce' )
        );
    } else {
        $product_id   = $product->get_id();
        $variation_id = 0;
        $variation    = array();   // simple products have no variation context
    }

    // ... build the item with derived attributes
}
```

The three branches correspond to the three product types you can encounter:

- **Variation product** → derive attributes; reconcile any client-supplied "any-slot" values.
- **Variable parent product** → reject; the client should have sent the variation ID.
- **Simple product** → force `$variation = []`; no attributes apply.

## The reconciliation pattern: specific values vs "any" values

A WooCommerce variation can pin a specific value for each attribute, or leave it as "Any" — meaning the variation accepts any of the parent's allowed values for that attribute. The two cases need different handling:

```php
private static function resolve_variation_attributes(
    \WC_Product $variation_product,
    array $requested_attributes
): array {
    $parent = wc_get_product( $variation_product->get_parent_id() );
    if ( ! $parent || ! $parent->is_type( ProductType::VARIABLE ) ) {
        return array();
    }

    $result = array();

    $all_attributes       = array_filter(
        $parent->get_attributes(),
        fn( $attribute ) => $attribute->get_variation()
    );
    $variation_attributes = wc_get_product_variation_attributes( $variation_product->get_id() );

    foreach ( $all_attributes as $name => $attribute ) {
        $key      = 'attribute_' . $name;
        $expected = $variation_attributes[ $key ] ?? '';

        if ( '' === $expected ) {
            // 'Any' attribute — client must supply a valid value.
            if ( ! isset( $requested_attributes[ $key ] ) ) {
                throw new \InvalidArgumentException(
                    sprintf(
                        /* translators: %s: attribute name. */
                        esc_html__( 'Attribute "%s" is required.', 'woocommerce' ),
                        $name
                    )
                );
            }

            if ( ! in_array( $requested_attributes[ $key ], $attribute->get_slugs(), true ) ) {
                throw new \InvalidArgumentException(
                    sprintf(
                        /* translators: 1: attribute name, 2: comma-separated allowed values. */
                        esc_html__( 'Invalid value posted for "%1$s". Allowed values: %2$s', 'woocommerce' ),
                        $name,
                        implode( ', ', $attribute->get_slugs() )
                    )
                );
            }

            $result[ $key ] = $requested_attributes[ $key ];
            continue;
        }

        // Specific-value attribute — server authoritative.
        if ( isset( $requested_attributes[ $key ] ) && $requested_attributes[ $key ] !== $expected ) {
            throw new \InvalidArgumentException(
                sprintf(
                    /* translators: 1: attribute name, 2: expected value. */
                    esc_html__( 'Invalid value posted for "%1$s". Expected "%2$s".', 'woocommerce' ),
                    $name,
                    $expected
                )
            );
        }

        $result[ $key ] = $expected;
    }

    return $result;
}
```

## Why this is the right shape

- **Variation product is the source of truth.** Specific-value attributes are pinned on the variation; the server fills them in. The client's payload is either ignored or validated against the server's value.
- **"Any" attributes need client input.** A variation that's "Color: blue, Size: any" doesn't know which size the user wants. The client must supply it; the server validates against the parent's allowed slugs.
- **Variable parents are a misuse signal.** A client that sends the parent product ID rather than a variation ID has confused the contract. Throw rather than silently accepting — the resulting row would have no `variation_id` and an empty `variation` array, with no way to recover the user's intent.

## Slug canonicalisation

WooCommerce stores attribute values as **lowercase taxonomy term slugs**. `wc_get_product_variation_attributes()` returns slugs in this form:

```php
[
    'attribute_pa_color' => 'blue',
    'attribute_pa_size'  => 'medium',
]
```

The strict `!==` comparison in the specific-value branch is correct because both sides come from canonicalised storage. Don't add case-insensitive matching — the cart doesn't, and consistency matters for predictable client behaviour.

When saving via `cart_item_key`, the cart already canonicalises slugs on its way into `cart_contents`, so the round-trip is clean. Direct API clients (using `product_id` + `variation`) need to send slugs in their canonical form.

## Mapping `InvalidArgumentException` to a REST 400

The reconciliation throws PHP `InvalidArgumentException` for validation failures. The route handler must catch it and return a 400 with a meaningful error code:

```php
protected function get_route_post_response( \WP_REST_Request $request ) {
    try {
        $item = LineItem::from_product( $lookup_id, $variation, $quantity );
    } catch ( \InvalidArgumentException $error ) {
        throw new RouteException(
            'woocommerce_rest_<feature>_invalid_variation',
            esc_html( $error->getMessage() ),
            400
        );
    }
    // ...
}
```

Without this, the abstract route's generic exception handler returns a 500 with `woocommerce_rest_unknown_server_error` — which obscures the actual problem from the client.

## Test coverage

Any route that accepts variation references should test all of:

1. Variation product passed as `product_id` → success, attributes derived from variation.
2. Variable parent passed as `product_id` → 400, "use variation ID" error.
3. Variation with all specific attributes → client sends nothing → server fills in correctly.
4. Variation with an "any" attribute → client must send valid value → success.
5. Variation with an "any" attribute → client sends invalid value → 400 with allowed values listed.
6. Variation with mismatched specific-value client value → 400 with expected value listed.
7. Cart-line save (via `cart_item_key`) → variation array round-trips correctly with no client-supplied data.

The variation path is the single most-likely place a future regression will land, and tests are the only way to lock the reconciliation behaviour.

## Reference

- [`CartController::parse_variation_data()`](../../../plugins/woocommerce/src/StoreApi/Utilities/CartController.php) — the canonical reconciliation pattern; mirror this.
- `wc_get_product_variation_attributes()` (WooCommerce core) — returns canonical slugs for a variation, with `''` for "any" slots.
- `WC_Product_Attribute::get_slugs()` (WooCommerce core) — returns the allowed slug list for an attribute on the parent product.
