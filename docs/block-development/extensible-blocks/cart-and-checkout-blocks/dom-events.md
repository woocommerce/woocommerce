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

_Example usage in WC Blocks:_ Cart block (via the `useStoreCart()` hook) listen to this event to know if they need to update their contents.

#### Added-event paths

| Path                                            | Trigger and cadence                                                                                                                                                              | Target and options                                                                                                    | `detail` and meaning                                                                                                          |
| ----------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| Interactivity API (iAPI) cart cycle             | Once after a settled mutation cycle that contains at least one successful add-origin mutation. It does not fire after an all-failed cycle or a successful remove-only cycle.     | `document.body`; bubbles; cancelable                                                                                  | `{ preserveCartData: true }`. This is an aggregate cycle signal, not an acknowledgement for a particular caller.              |
| Classic `@wordpress/data` `addItemToCart` thunk | After each successful call. A caught API failure does not dispatch it.                                                                                                           | `document.body`; bubbles; cancelable                                                                                  | `{ preserveCartData: true }`.                                                                                                 |
| Product-form processed response handler         | After the handler processes either a successful or an error HTTP response. An outer rejected fetch does not dispatch it.                                                         | `document.body`; bubbles; cancelable                                                                                  | `{ preserveCartData: true }`. Because processed error responses also dispatch it, this path does not uniformly prove success. |
| jQuery `added_to_cart` bridges                  | Each active bridge registration translates every underlying jQuery event. The Cart and Mini-Cart registrations can both be active, so there is no global exactly-once guarantee. | `document.body`; the Cart registration does not bubble, the Mini-Cart registration bubbles, and neither is cancelable | `{}`. This path reports translation of the jQuery event rather than sharing the cadence or payload of the other paths.        |

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

### `wc-blocks_removed_from_cart`

This event is the equivalent to the jQuery event `removed_from_cart` triggered by WooCommerce core. It indicates that a product has been removed from the cart.

_Example usage in WC Blocks:_ Cart and Mini-Cart blocks (via the `useStoreCart()` hook) listen to this event to know if they need to update their contents.
