# Tombstone / deleted-product patterns

Routes that persist references to other resources (products, orders, posts) need a policy for what happens when the referenced thing is deleted. The choice between **cascade delete** and **tombstone** shapes the entire response contract.

## Decide upfront: cascade or tombstone

| Approach | Behaviour | When to use |
|---|---|---|
| **Cascade delete** | The referencing row disappears with the referenced thing. Simple, no extra storage. | Cart line items — once the product is gone, the cart line has no value. |
| **Tombstone** | The referencing row survives, surfaces as a "deleted" state, optionally backed by snapshot data. | Saved-for-later, order line items, anything where the user expectation is "this stays in my history regardless of what the merchant does." |

Pick one and document it. A route that's neither — survives the deletion but renders blank — is the worst of both: clutter without context.

## What a tombstone looks like on the wire

The standard shape is a `product_exists` boolean plus shape-stable fields that fall back to snapshots server-side:

```json
{
    "key": "...",
    "id": 42,
    "product_id": 42,
    "variation_id": 0,
    "quantity": 1,
    "product_exists": false,
    "name": "Hoodie with Zipper",       ← from snapshot
    "permalink": "",
    "images": [],
    "variation": [],
    "prices": null,
    "date_added_gmt": "..."
}
```

The frontend reads one shape regardless of branch:

```javascript
{ item.product_exists ? <FullProductRow /> : <UnavailableRow /> }
```

— with both branches consuming the same `name`, `prices`, `images` fields. The `product_exists` flag only switches presentation, not data shape.

## Snapshot only what the UI needs

Don't snapshot fields just because you can. Each snapshotted field is permanent storage that has to be migrated when its format changes, and a contract field that has to be maintained forever.

A typical "save for later" UX needs:

- **Title** — for labelling. Snapshot `$product->get_title()` for simple products, or `$product->get_name()` for variations (variation-aware label).
- **Image** — usually a placeholder is fine for tombstones. If you do snapshot the image, snapshot the attachment ID and resolve at read time, not the URL (URLs change on domain moves, CDN switches, image regeneration).

A typical "saved when on sale" UX additionally needs:

- **Currency code + minor unit** — so you can correctly format a snapshotted price.
- **Active price** — the value the user saw when they saved.
- **Regular price + sale price** — to show "you saved this when it was on sale at €X (regular €Y)."

If your UX doesn't show prices on tombstones at all, **don't snapshot prices**. `prices: null` is a clean shape; the frontend renders no price label and shows a "no longer available" affordance.

## Format consistency between live and tombstone branches

The live and tombstone branches must produce **the same shape and format**. Anything the live path does to the data must be mirrored in the tombstone path, or the two outputs drift.

Concrete failures this prevents:

- **XSS via the tombstone branch.** If the live branch escapes `name` via `prepare_html_response()` but the tombstone branch sets it raw from a snapshot, the tombstone is an XSS vector. Stored XSS is hard to spot in code review because the danger only manifests for deleted products.
- **Format mismatch on prices.** Live `prices.price` is minor-units integer-string (e.g. `"1999"`). If `price_at_save` is a major-units decimal string (e.g. `"19.99"`), the frontend's money formatter silently corrupts tombstones — same field name, same response, two formats.
- **Missing currency context.** Live prices include `currency_code`, `currency_minor_unit`, etc. If tombstone prices are a single value, they can't be formatted correctly under multi-currency or after a store-level currency change.

Two strategies for keeping them aligned:

1. **Funnel both branches through one formatter.** Build a synthetic product object (or a tombstone-aware accessor) that the response builder treats uniformly. One code path, no branches to drift.
2. **Mirror the live path explicitly.** Apply the same escapers, formatters, and helpers in the tombstone branch as the live branch. Add a comment cross-referencing the two so the next maintainer sees the parity invariant.

```php
if ( $has_product ) {
    $response['name']   = $this->prepare_html_response( $product->get_title() );
    $response['prices'] = (object) $this->get_prices( $product );
} else {
    $response['name']   = $this->prepare_html_response( $item['product_title_at_save'] ?? '' );
    $response['prices'] = null;
}
```

Both branches escape `name`. Both branches use the same key. Format-consistency property maintained.

## Don't expose snapshot fields as separate response keys

If `product_title_at_save` is the source of the tombstone fallback, **don't also expose it as its own response field**. That creates two fields claiming to represent the same string — `name` (escaped, public) and `product_title_at_save` (often unescaped, internal). The asymmetry is an XSS vector, and the duplication is noise.

Keep snapshot fields server-internal. Use them to populate the public field; don't surface them as schema properties.

## Format snapshots in canonical units at storage time

If you do snapshot prices, decide between two paths:

1. **Snapshot in canonical Store API units (minor-units integer-string).** Run the price through `prepare_money_response()` at write time. Storage and live shape match; the schema can emit it raw. Trade-off: existing rows are in the canonical format from day one.
2. **Snapshot in raw decimal form, format on read.** Storage stays human-readable; the schema runs `price_at_save` through `prepare_money_response()` in the response builder. Trade-off: every read pays a tiny formatting cost (negligible).

Either is fine; the wrong answer is "snapshot raw decimal AND emit raw decimal." Same response will then have two different price formats — `prices.price` minor-units, `price_at_save` major-units — and the frontend has to special-case.

**Document the unit on the schema description.** A field documented as just "Price" is ambiguous. Specify "Minor units (e.g. cents). Use `currency_minor_unit` to format."

## Mind the deletion-time gap

Some snapshot fields can't be reconstructed at read time once the source is gone:

- **Variation attributes** — once the variation product is deleted, `wc_get_product()` returns null. The variation context (e.g., "Color: Blue") is lost unless you snapshotted it.
- **Custom item data** — if the product had per-cart-line custom data, it's gone with the variation.
- **Image URLs** — if you didn't snapshot the attachment ID, you can't reconstruct the path.

For each of these, decide at design time:

- **Snapshot it** → permanent storage, schema field, format-consistency obligation.
- **Skip it** → tombstone shows less than the live row, accept that limitation in the UX.

For variation attributes specifically: if the variation might be deleted while the row is still saved, snapshot the rendered attribute labels at save time. Otherwise the tombstone shows "T-Shirt" with no way to distinguish which variation was saved.

## Anti-patterns to avoid

- **Cascade-delete dressed up as tombstone.** A `product_exists: false` row that has empty `name`, no `prices`, no `images` is just clutter. If you can't show anything useful, cascade delete instead.
- **Two parallel response paths satisfying the same schema with no enforced parity.** Anything new added to the live path needs a matching change to the tombstone path. Without a test pinning identical shapes, this drifts. Add a snapshot test.
- **Exposing internal snapshot fields publicly.** Snapshot fields exist for the implementation's tombstone fallback; they aren't part of the contract. Keep them in storage, populate the public field server-side.
- **Snapshotting in a different unit/format from the live branch.** Forces every consumer to special-case the tombstone branch. Use the same formatter on both.

## Reference

- [`AbstractSchema::prepare_money_response()`](../../../plugins/woocommerce/src/StoreApi/Schemas/V1/AbstractSchema.php) — canonical money formatter (minor-units integer-string).
- [`AbstractSchema::prepare_html_response()`](../../../plugins/woocommerce/src/StoreApi/Schemas/V1/AbstractSchema.php) — canonical HTML escaper.
- See [schema-design.md](schema-design.md) for the field-discipline rules that prevent tombstone-related schema bloat.
