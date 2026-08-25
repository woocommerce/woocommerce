---
post_title: DOM events
sidebar_label: DOM Events
sidebar_position: 3
---

# DOM events

Some blocks need to react to certain events in order to display the most up to date data or behave in a certain way. That's the case of the Cart block, for example, that must listen to 'add to cart' events in order to update the cart contents; or the Mini-Cart block, that gets opened every time a product is added to the cart.

## WooCommerce core events in WooCommerce Blocks

WooCommerce core uses jQuery events to trigger and listen to certain events, like when a product is added or removed from the cart. In WooCommerce Blocks, we moved away from using jQuery, but we still need to listen to those events. To achieve that, we have a utility named [`translatejQueryEventToNative()`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/assets/js/base/utils/legacy-events.ts) that listens to those jQuery events, and every time one is triggered, it triggers an associated DOM native event (with the `wc-blocks_` prefix).

## WooCommerce Blocks events

### `wc-blocks_adding_to_cart`

This event is the equivalent to the jQuery event `adding_to_cart` triggered by WooCommerce core. It indicates that the process of adding a product to the cart was sent to the server, but there is still no indication on whether the product was successfully added or not.

_Example usage in WC Blocks:_ Mini-Cart block listens to this event to append its dependencies.

### `wc-blocks_added_to_cart`

This native event has several producers. Its cadence and meaning depend on the path that dispatched it; it is not always a success acknowledgement and is not guaranteed to fire only once for an underlying action.

_Example usage in WC Blocks:_ Cart and Mini-Cart blocks (via the `useStoreCart()` hook) listen to this event to know if they need to update their contents.

#### Added-event paths

| Path | Trigger and cadence | Target and options | `detail` and meaning |
| --- | --- | --- | --- |
| Interactivity API (iAPI) cart cycle | Once after a settled mutation cycle that contains at least one successful add-origin mutation. It does not fire after an all-failed cycle or a successful remove-only cycle. | `document.body`; bubbles; cancelable | `{ preserveCartData: true }`. This is an aggregate cycle signal, not an acknowledgement for a particular caller. |
| Classic `@wordpress/data` `addItemToCart` thunk | After each successful call. A caught API failure does not dispatch it. | `document.body`; bubbles; cancelable | `{ preserveCartData: true }`. |
| Product-form processed response handler | After the handler processes either a successful or an error HTTP response. An outer rejected fetch does not dispatch it. | `document.body`; bubbles; cancelable | `{ preserveCartData: true }`. Because processed error responses also dispatch it, this path does not uniformly prove success. |
| jQuery `added_to_cart` bridges | Each active bridge registration translates every underlying jQuery event. The Cart and Mini-Cart registrations can both be active, so there is no global exactly-once guarantee. | `document.body`; the Cart registration does not bubble, the Mini-Cart registration bubbles, and neither is cancelable | `{}`. This path reports translation of the jQuery event rather than sharing the cadence or payload of the other paths. |

The direct iAPI, classic `@wordpress/data`, and product-form paths have this envelope:

```javascript
new CustomEvent( 'wc-blocks_added_to_cart', {
	bubbles: true,
	cancelable: true,
	detail: { preserveCartData: true },
} );
```

The jQuery bridges use the same event name and target with `detail: {}` and `cancelable: false`; only the Mini-Cart registration sets `bubbles: true`.

For the iAPI path, a mutation cycle can contain work from unrelated callers. One successful add-origin mutation causes the single cycle event even when a sibling mutation from another caller fails. The failed caller still receives a failed `addCartItem` outcome. A successful keyed quantity update made through `addCartItem` also has add origin, so this event does not prove that a new cart line was created.

`preserveCartData` tells listeners whether to retain current cart data. When it is `false`, listeners can invalidate and refetch cart data. Producers that have already updated the store set it to `true` to avoid an unnecessary refetch.

### `wc-blocks_store_sync_required`

This event asks the iAPI and classic `@wordpress/data` stores to synchronize, or delivers a saved-for-later item. Its `detail` is a discriminated envelope. Consumers must check `detail.type` before reading fields belonging to a variant.

| `detail.type` | Trigger and cadence | Target and options | Variant fields |
| --- | --- | --- | --- |
| `from_iAPI` | Once after a settled iAPI mutation cycle containing any successful mutation. It does not fire after an all-failed cycle. | `window`; does not bubble; not cancelable | `quantityChanges`; this is the only cycle-scoped variant. |
| `from_@wordpress/data` | After an eligible classic cart-data change. | `window`; does not bubble; not cancelable | No `quantityChanges`. |
| `shopper-list-item-added` | After a successful save-for-later call. | `window`; does not bubble; not cancelable | `slug: 'saved-for-later'` and the saved response in the envelope field `item`. |

The exact `detail` envelopes are:

```javascript
// iAPI cart mutation cycle.
{
	type: 'from_iAPI',
	quantityChanges: {
		productsPendingAdd: [ 123 ],
		cartItemsPendingQuantity: [ 'cart-item-key' ],
		cartItemsPendingDelete: [ 'removed-cart-item-key' ],
	},
}

// Classic @wordpress/data cart change.
{
	type: 'from_@wordpress/data',
}

// Successful save-for-later response.
{
	type: 'shopper-list-item-added',
	slug: 'saved-for-later',
	item,
}
```

These are DOM event envelopes, not an exported TypeScript type. The `item` value is the saved response carried by the envelope; this contract does not define a nested schema for it.

#### iAPI cycle contract

The iAPI dispatcher applies separate success gates to its two events:

- If every mutation fails, it dispatches neither `wc-blocks_added_to_cart` nor `wc-blocks_store_sync_required`.
- If at least one mutation succeeds, it dispatches one `wc-blocks_store_sync_required` event with `detail.type === 'from_iAPI'`.
- It dispatches one `wc-blocks_added_to_cart` event only when at least one successful mutation has add origin. A remove-only cycle therefore dispatches sync but not added.

The `quantityChanges` object aggregates metadata from successful mutations only. Failed mutations contribute nothing. Each optional category is unioned independently, duplicate values collapse, and a category with no values can be absent:

- `productsPendingAdd: number[]` contains the submitted product IDs for successful keyless adds. The ID remains exactly the submitted simple or parent product ID, or the submitted selected-variation ID; it is not replaced with an inferred parent ID.
- `cartItemsPendingQuantity: string[]` contains cart-item keys for successful keyed quantity updates. Such an update is add-origin for deciding whether to dispatch the added event, but it contributes only its key here, not a `productsPendingAdd` value.
- `cartItemsPendingDelete: string[]` contains cart-item keys for successful removals.

The cycle and its deduplicated categories are aggregate synchronization signals. Do not derive numbers of products, requests, callers, shopper gestures, occurrences, or individual successes from the event count or from the number of entries in `quantityChanges`. Callers that need the result of their own `addCartItem` call must use that call's outcome. Aggregate consumers must first require `detail.type === 'from_iAPI'` and only then read `detail.quantityChanges`.

When an iAPI cycle owes both events, it dispatches `wc-blocks_added_to_cart` and then `wc-blocks_store_sync_required` synchronously, before the per-call outcomes settle. The later sync payload is not available inside the synchronously earlier added-event handler. Listen for the narrowed sync variant when cycle contents are required.

### `wc-blocks_removed_from_cart`

This event is the equivalent to the jQuery event `removed_from_cart` triggered by WooCommerce core. It indicates that a product has been removed from the cart.

_Example usage in WC Blocks:_ Cart and Mini-Cart blocks (via the `useStoreCart()` hook) listen to this event to know if they need to update their contents.
