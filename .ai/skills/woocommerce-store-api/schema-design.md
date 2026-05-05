# Schema design

The schema is the public contract for a Store API resource. It declares what the response contains, drives input validation, and is what introspection tools (OpenAPI generators, `OPTIONS` requests, type generators) read. Get it right at design time — schema fields are easier to add later than to retract.

## The schema is the public contract

`AbstractSchema::get_properties()` declares the response shape. Anything in the wire response **must** be declared here. Conversely, anything declared here **must** appear in the response.

When the two diverge, every consumer pays:

- WP's REST introspection (`OPTIONS /your-route`, the `/wp-json/wc/store/v1` index) reports a shape that doesn't match reality.
- A frontend developer reading the schema docs assumes there's no `extra_field` on the response and either misses it or works around it.
- Future tools that auto-generate clients/types from the schema produce broken types.
- The next person reviewing the route asks "is this an undeclared field on purpose, or did someone forget to update the schema?" — every time.

## Single source of truth: build responses inside `get_item_response()`

Don't build a response by appending fields to the schema's output:

```php
// ❌ Don't — schema doesn't know about the extra field
$response          = $schema->get_item_response( $resource );
$response['extra'] = compute_extra( $resource );
return new \WP_REST_Response( $response );
```

Add the field to `get_properties()` and populate it inside the schema's own `get_item_response()`:

```php
// ✅ Do — schema declares and produces the field
public function get_properties() {
    return array(
        // ...
        'extra' => array(
            'description' => __( 'Computed extra value.', 'woocommerce' ),
            'type'        => 'string',
            'context'     => array( 'view', 'edit' ),
            'readonly'    => true,
        ),
    );
}

public function get_item_response( $resource ) {
    return array(
        // ...
        'extra' => $this->compute_extra( $resource ),
    );
}
```

The route then becomes a one-liner: `return new \WP_REST_Response( $schema->get_item_response( $resource ), 201 );`. No bolting, no drift.

## Schema must match all status paths

Different methods on the same route can produce different response shapes. The schema must cover all of them:

- `GET /items` → list of items.
- `POST /items` → the single newly-added item (status 201).
- `DELETE /items/{key}` → 204 with empty body — schema not invoked.
- `GET /` → list metadata; on writes, return the same metadata or split into a write schema.

Common mistake: declaring the GET-list shape on the schema while the POST/DELETE routes return something different. The fix is one of:

1. Declare the union shape on one schema (works when fields are additive, e.g., `items: array` only populated on writes).
2. Use separate schemas for read and write responses (cleaner if the shapes differ structurally).
3. Have writes return only the new/deleted member, not the parent — collection-add convention. See [rest-conventions.md](rest-conventions.md).

Option 3 is usually best: it keeps each route's schema simple and matches the cart precedent.

## Field discipline

### Don't ship fields with only one possible value

A schema property with a value that's hardcoded forever is dead surface. Examples:

- `is_public: false` shipped with no way to flip it.
- A `read_only: true` flag where everything is read-only.
- An `errors: []` array that's always empty.

Add the field when the underlying behaviour ships. Adding fields is backwards-compatible; removing them is a breaking change.

### Don't expose two fields representing the same data

Don't ship both `name` (escaped) and `product_title_at_save` (raw) for the same string. The escaped/raw asymmetry is an XSS vector — frontends will read whichever they reach first.

Pick one path. If a snapshot field exists for internal storage, keep it server-side: use it to populate the public field via the response builder, but don't surface it as its own schema property.

### Don't expose internal storage fields

Snapshot/cache/computed fields that exist for the implementation's benefit shouldn't leak. A field that only powers a server-side fallback doesn't need to be in the response — it's used at response-build time and that's it.

### Don't expose unstable values

A `date_created_gmt` field that changes on every read until the first write is worse than no field at all. Either persist eagerly so the value is stable, or omit until persistence is meaningful.

### Don't accept fields the route ignores

If a route accepts `quantity` but always coerces it to `1`, drop it from the route args. Accepting input the route silently discards is misleading.

## Sanitisation

All string fields must run through escaping before output. The Store API has dedicated helpers:

- `prepare_html_response( $string )` — for any string that might be rendered as HTML. Strips disallowed tags via `wp_kses_post`-equivalent rules.
- `prepare_money_response( $price, $decimals )` — for any monetary value. Produces minor-units integer-strings (e.g. `"1999"` for $19.99 in a 2-decimal currency). This is the canonical Store API money format.
- `wc_rest_prepare_date_response( $datetime )` — for date fields. Produces ISO-8601 strings.

**Apply the same sanitisation in every code path that produces a field.** If one branch escapes a string via `prepare_html_response()` but a fallback branch sets it raw from storage, the fallback is an XSS vector. Run every branch through the same escapers and formatters, or funnel them through one builder so they can't drift.

**Document units on the schema description.** A field documented as just "Price" is ambiguous. Specify "Price in minor units (e.g. cents)" or "Price as decimal string (e.g. 19.99)" so consumers know how to format.

## Per-context properties

`'context' => array( 'view', 'edit' )` controls when a property is included in the response. Use sparingly — most Store API responses are single-context. Don't use context to hide implementation details; use it for genuine view-vs-edit differentiation (e.g., admin-only fields).

## Schema validation pulls double duty

The schema's `type` and `required` declarations drive WP's argument validation:

- `'type' => 'integer'` rejects non-integer inputs with a 400 before the route handler runs.
- `'minimum' => 1, 'maximum' => 999` enforces bounds.
- `'enum' => [...]` restricts to a known set.

Use these instead of validating inside the handler. The schema is the contract, and the validator runs before any of your code — letting it do the work means you can't accidentally skip it.

When you need bounds-checking that the schema can't express (e.g., "must reference an existing product"), validate early in the handler and throw a `RouteException` with a meaningful error code:

```php
$product = wc_get_product( $product_id );
if ( ! $product ) {
    throw new RouteException(
        'woocommerce_rest_unknown_product',
        esc_html__( 'No product exists for the supplied ID.', 'woocommerce' ),
        404
    );
}
```

## Document non-obvious behaviour

If a field has merge semantics, default values, or interactions with other fields, spell them out in the `description`:

```php
'quantity' => array(
    'description' => __(
        'Quantity to save. If an item with the same product/variation already exists, its quantity is replaced with this value.',
        'woocommerce'
    ),
    'type'    => 'integer',
    'minimum' => 1,
    'default' => 1,
),
```

The schema description is what API clients read. It's the only place merge-on-resave, server-authoritative attribute reconciliation, or default-fallback behaviours can be documented for consumers.

## Anti-patterns to avoid

- **Bolting fields onto the response post-schema** — diverges schema and reality.
- **Schema declares a field; route conditionally omits it** — clients can't tell whether the field is missing because of context or a bug.
- **Documenting in the route what should be on the schema** — clients reading the schema (or its introspection) won't see it.
- **`description` strings that don't actually describe** (`'description' => __('Slug.', ...)`) — wasted opportunity to encode behaviour and units.
- **Optional fields with `default`-via-PHP-fallback inside `get_item_response()`** — declare `default` on the schema instead so it's introspectable.

## Reference

- [`AbstractSchema`](../../../plugins/woocommerce/src/StoreApi/Schemas/V1/AbstractSchema.php) — base class, helpers (`prepare_html_response`, `prepare_money_response`).
- [`CartSchema`](../../../plugins/woocommerce/src/StoreApi/Schemas/V1/CartSchema.php) — canonical example of a non-trivial schema.
- [`CartItemSchema`](../../../plugins/woocommerce/src/StoreApi/Schemas/V1/CartItemSchema.php) — demonstrates `ProductItemTrait` usage and the live-branch response pattern.
