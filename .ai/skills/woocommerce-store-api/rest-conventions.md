# REST URL and response conventions

The Store API follows REST conventions more strictly than the older `/wc/v3/*` admin API. Read this before designing a new route.

## Where data goes

| Where | What it's for |
| --- | --- |
| **Path** (`/items/{key}`) | Identifies *which* resource. Required for any single-resource operation. |
| **Body** (JSON) | Payload for create/update. POST, PUT, PATCH. |
| **Query string** (`?since=...`) | Filters, pagination, sort options on GET. |

**Don't accept POST data via query string.** WordPress's `WP_REST_Request::get_param()` is permissive — it'll find values from any source. That's a debugging convenience, not a design statement. Production clients should send JSON bodies; document the canonical shape in the schema.

**Don't put a body on a GET.** RFC 9110 §9.3.1 leaves GET-body semantics undefined and SHOULD NOT's the practice; servers, proxies, and CDNs are free to reject or ignore the body. Browsers' `fetch()` refuses to send one outright. Filter via the query string instead.

**GET must not have side effects.** Caches, prefetchers, browser history, retries, security scanners — anything that thinks GET is safe will silently repeat the request. Auto-create-on-read patterns are allowed only as in-memory materialisation; persist on the first explicit write, never inside a GET handler.

## Collection vs item vs action routes

Store API routes split into three shapes, each with a different response convention:

| Shape | URL | Returns | Example |
| --- | --- | --- | --- |
| Collection-add | `POST /items` | The added single item, status 201 | `CartItems::get_route_post_response()` |
| Collection-delete | `DELETE /items/{key}` | 204 with null body | `CartItemsByKey::get_route_delete_response()` (in `CartItemsByKey.php`) |
| Action on parent | `POST /cart/add-item`, `POST /cart/apply-coupon` | The whole parent resource | `CartAddItem`, `CartApplyCoupon` |

The split matters: action routes return the parent because they're "do something to the parent" — the client needs the new aggregate state (cart totals, coupon discounts) in one round-trip. Collection routes return the new/deleted member; clients reconcile by splicing the response into local state.

Don't mix the two. "POST /items returns the whole collection" is awkward, breaks client-side reconciliation patterns, and locks the schema into declaring a field (the items array) that doesn't fit the resource shape of the route.

## Status codes

| Code | When |
| --- | --- |
| `200` | GET success; PATCH/PUT success returning the updated resource. |
| `201 Created` | POST that creates a new resource. Body is the new resource. |
| `204 No Content` | DELETE success. Empty body. |
| `400 Bad Request` | Input validation failure (schema rejected the args, or a custom validator threw). |
| `401 Unauthorized` | Missing auth, missing nonce, expired session. |
| `403 Forbidden` | Auth present but insufficient (wrong user, wrong cap, invalid nonce). |
| `404 Not Found` | Resource doesn't exist (`<feature>_not_found`) **or** route doesn't exist (`rest_no_route`). |
| `409 Conflict` | State precondition failed (rare; e.g. duplicate that the API refuses to merge). |

`rest_no_route` (404) and `<feature>_not_found` (404) are different things. The first means "WP couldn't match your URL to any registered route" — usually a typo. The second means "the route matched but the resource doesn't exist." Distinguish them in error messages.

## Idempotency

State-changing routes that can plausibly be retried (POST creates, DELETE removes) should produce stable identifiers and behave deterministically.

**For collection-add routes:**

- Use a deterministic hash of the resource's identity tuple as the storage key.
- `ksort()` any input arrays before hashing — JSON object key order isn't guaranteed across clients, so `{a:1, b:2}` and `{b:2, a:1}` must produce the same key.
- Mirror existing patterns: `WC_Cart::generate_cart_id()` is the reference for cart-line identity; replicate its `ksort` step.
- Decide and document re-save semantics: replace, sum, or reject. Surface this in the schema description for the `quantity` (or equivalent) field.

```php
private static function generate_key( int $product_id, int $variation_id, array $variation ): string {
    $id_parts = array( $product_id );

    if ( $variation_id ) {
        $id_parts[] = $variation_id;
    }

    if ( ! empty( $variation ) ) {
        ksort( $variation );  // canonicalise for stable hashing
        $variation_key = '';
        foreach ( $variation as $k => $v ) {
            $variation_key .= trim( (string) $k ) . trim( (string) $v );
        }
        $id_parts[] = $variation_key;
    }

    return md5( implode( '_', $id_parts ) );
}
```

**Pin the contract with a test.** Without one, a future refactor (e.g. switching to `wp_json_encode`) can silently regress idempotency.

## URL hierarchy

Resource identifiers in the path should follow the resource hierarchy:

```text
/{collection}                               ← collection of parents
/{collection}/{slug}                        ← one specific parent
/{collection}/{slug}/items                  ← items collection inside a parent
/{collection}/{slug}/items/{key}            ← one specific item
```

Each segment adds one identifier. Avoid flat URLs like `/items/{key}` when keys are scoped per-parent — the same identifier can collide across parents and the URL can't disambiguate.

## Anti-patterns to avoid

- **Auto-creating a resource on GET.** A GET that triggers a database write is surprising and breaks under any retry/cache scenario. Materialise lazily in memory and persist on the first POST.
- **Returning the parent on POST `/items`.** Mixes the collection-add and action shapes. Clients can't reconcile without re-parsing the whole parent. Stick to "POST returns the added member."
- **Bolting fields onto the response after the schema produced it.** The schema and the wire format diverge; introspection lies. Add the field to `get_properties()` and populate it inside `get_item_response()`.
- **Accepting JSON-encoded strings in query parameters.** A request like `?variation={...}` arrives as a string; the validator can't usefully coerce it. Send structured data in the body.
- **Mixing stable identifiers with debug aliases.** Don't accept `?product_id=42` AND `{"product_id": 42}` in the body for production clients. Pick the canonical shape, document it, treat the other as undocumented behaviour.

## Reference routes

Use these as canonical examples for new routes:

- **Read collection:** [`Cart::get_route_response`](../../../plugins/woocommerce/src/StoreApi/Routes/V1/Cart.php).
- **Read single item:** [`CartCouponsByCode::get_route_response`](../../../plugins/woocommerce/src/StoreApi/Routes/V1/CartCouponsByCode.php).
- **Collection POST:** [`CartItems::get_route_post_response`](../../../plugins/woocommerce/src/StoreApi/Routes/V1/CartItems.php).
- **Single-item DELETE (204 + null):** [`CartItemsByKey::get_route_delete_response`](../../../plugins/woocommerce/src/StoreApi/Routes/V1/CartItemsByKey.php).
- **Collection DELETE (200 + empty array, clears all):** [`CartItems::get_route_delete_response`](../../../plugins/woocommerce/src/StoreApi/Routes/V1/CartItems.php) — note this is "empty everything" semantics, not "delete one member."
- **Action route:** [`CartAddItem`](../../../plugins/woocommerce/src/StoreApi/Routes/V1/CartAddItem.php), [`CartApplyCoupon`](../../../plugins/woocommerce/src/StoreApi/Routes/V1/CartApplyCoupon.php).

When in doubt, copy the cart precedent. The Store API was built around the cart's response patterns, so aligning with cart routes minimises surprise for both reviewers and frontend consumers.
