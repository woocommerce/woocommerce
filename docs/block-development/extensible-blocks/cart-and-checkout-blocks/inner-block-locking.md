---
post_title: Inner block locking in Cart, Checkout, and Mini-Cart
sidebar_label: Inner block locking
sidebar_position: 6
---

# Inner block locking in Cart, Checkout, and Mini-Cart

The Cart, Checkout, and Mini-Cart blocks are composed of modular inner blocks. To protect essential e-commerce flows while keeping optional storefront elements customizable, WooCommerce locks critical structural and transactional blocks by default and leaves optional or presentational blocks unlocked and removable.

## How block locking works

WooCommerce combines three mechanisms to manage inner block structure in the block and site editors:

1. **Block-level `attributes.lock` defaults** — Inner blocks can either declare explicit default `{ remove, move }` flags in their attributes (`block.json` or `attributes.ts`/`attributes.tsx`) or leave `attributes.lock` unset. When `remove: true`, the block cannot be deleted; when `move: true`, it cannot be reordered. Explicit `false` defaults (`remove: false`, `move: false`) override a parent container's `templateLock`, whereas an unset `lock` attribute inherits parent `templateLock` behavior.
2. **Core lock UI (`supports.lock`)** — Most essential blocks set `"lock": false` under `supports` so merchants cannot unlock and delete critical checkout steps from the toolbar or List View. Optional blocks keep `"lock"` enabled (the WordPress default `true`), allowing merchants to lock or unlock them freely.
3. **Container `templateLock` and `useForcedLayout`** — Top-level containers (`woocommerce/cart`, `woocommerce/filled-cart-block`, `woocommerce/checkout`, `woocommerce/filled-mini-cart-contents-block`) use `templateLock="insert"` (or `templateLock="all"` on `woocommerce/mini-cart-title-block`). Containers using `useForcedLayout` (such as `woocommerce/cart-items-block`, `woocommerce/checkout-fields-block`, `woocommerce/checkout-totals-block`, and `woocommerce/mini-cart-contents`) automatically re-insert required core blocks and registered third-party inner blocks that declare `remove: true` in their lock defaults (`getMissingBlocks` / `isBlockLocked`) if they are missing from the layout; registered inner blocks without `remove: true` are not automatically restored after removal.

## Why critical blocks are locked

Locking prevents merchants or theme edits from accidentally breaking required storefront behavior:

- **Preventing broken checkout or cart flows** — Deleting blocks such as Line Items, Contact Information, Shipping/Billing Address, Payment Options, or Actions would stop shoppers from entering required details or completing an order.
- **Protecting Mini-Cart template parts** — `woocommerce/mini-cart-contents` is locked (`remove: true`, `move: true`, `supports.lock: false`) inside Site Editor Mini-Cart template parts because removing or unlocking the top-level contents wrapper breaks the slide-out drawer UI.
- **Preserving third-party extensibility** — `useForcedLayout` keeps required core blocks and extension-registered inner blocks (`area` / `parent` integrations) whose registered lock defaults specify `remove: true` present in their target sections; registered extension blocks without `remove: true` are not automatically re-inserted if removed.

> If you want to allow extra WordPress or custom blocks inside Cart and Checkout areas, see [Inner block types](/docs/block-development/extensible-blocks/cart-and-checkout-blocks/filters-in-cart-and-checkout/additional-cart-checkout-inner-block-types/).

## Cart inner blocks (`woocommerce/cart`)

### Unlocked and removable

| Block name | Title | Default `lock` (`remove` / `move`) | User can toggle lock (`supports.lock`) | Reason |
| --- | --- | --- | --- | --- |
| `woocommerce/cart-accepted-payment-methods-block` | Accepted Payment Methods | Unset (inherits parent lock if any) | Yes | Optional payment method icon badges |
| `woocommerce/cart-order-summary-heading-block` | Heading | `remove: false`, `move: false` | Yes | Optional section heading inside Order Summary |
| `woocommerce/cart-order-summary-coupon-form-block` | Coupon Form | `remove: false`, `move: false` | Yes | Optional for stores that do not offer or want inline coupon entry |
| `woocommerce/cart-cross-sells-block` | Cart Cross-Sells | Unset (inherits parent lock if any) | Yes | Optional legacy cross-sells container (its inner `woocommerce/cart-cross-sells-products-block` is locked; default templates use `woocommerce/product-collection`) |

### Non-removable or fully locked

| Block name | Title | Default `lock` (`remove` / `move`) | User can toggle lock (`supports.lock`) |
| --- | --- | --- | --- |
| `woocommerce/cart-totals-block` | Cart Totals | `remove: true`, `move: false` | No |
| `woocommerce/cart-order-summary-totals-block` | Totals | `remove: true`, `move: false` | No |
| `woocommerce/cart-order-summary-taxes-block` | Taxes | `remove: true`, `move: false` | No |
| `woocommerce/filled-cart-block` | Filled Cart | `remove: true`, `move: true` | No |
| `woocommerce/empty-cart-block` | Empty Cart | `remove: true`, `move: true` | No |
| `woocommerce/cart-items-block` | Cart Items | `remove: true`, `move: true` | No |
| `woocommerce/cart-line-items-block` | Cart Line Items | `remove: true`, `move: true` | No |
| `woocommerce/cart-order-summary-block` | Order Summary | `remove: true`, `move: true` | No |
| `woocommerce/cart-order-summary-subtotal-block` | Subtotal | `remove: true`, `move: true` | No |
| `woocommerce/cart-order-summary-discount-block` | Discount | `remove: true`, `move: true` | No |
| `woocommerce/cart-order-summary-fee-block` | Fees | `remove: true`, `move: true` | No |
| `woocommerce/cart-order-summary-shipping-block` | Shipping | `remove: true`, `move: true` | No |
| `woocommerce/cart-express-payment-block` | Express Checkout | `remove: true`, `move: true` | No |
| `woocommerce/proceed-to-checkout-block` | Proceed to Checkout | `remove: true`, `move: true` | No |
| `woocommerce/cart-cross-sells-products-block` | Cart Cross-Sells Products | `remove: true`, `move: true` | No |

## Checkout inner blocks (`woocommerce/checkout`)

### Unlocked, removable, or movable

| Block name | Title | Default `lock` (`remove` / `move`) | User can toggle lock (`supports.lock`) | Notes |
| --- | --- | --- | --- | --- |
| `woocommerce/checkout-order-summary-coupon-form-block` | Coupon Form | `remove: false`, `move: false` | Yes | Optional coupon form in Order Summary |
| `woocommerce/checkout-order-note-block` | Order Note | `remove: false`, `move: true` | Yes | Removable if customer notes are not needed; fixed position unless unlocked |
| `woocommerce/checkout-terms-block` | Terms and Conditions | Unset (inherits parent lock if any) | Yes | Hidden from inserter on new checkouts; replaced by settings on the Actions block |
| `woocommerce/checkout-additional-information-block` | Additional information | `remove: true`, `move: false` | Yes | Non-removable by default, movable within Checkout Fields. If unlocked and removed, `useForcedLayout` can re-insert it because its registered default is `remove: true`. |

### Structural, non-removable, or fully locked

| Block name | Title | Default `lock` (`remove` / `move`) | User can toggle lock (`supports.lock`) |
| --- | --- | --- | --- |
| `woocommerce/checkout-totals-block` | Checkout Totals | Container (`templateLock="insert"` on `woocommerce/checkout`) | No |
| `woocommerce/checkout-order-summary-cart-items-block` | Cart Items | `remove: true`, `move: false` | No |
| `woocommerce/checkout-order-summary-totals-block` | Totals | `remove: true`, `move: false` | No |
| `woocommerce/checkout-fields-block` | Checkout Fields | `remove: true`, `move: true` | No |
| `woocommerce/checkout-express-payment-block` | Express Checkout | `remove: true`, `move: true` | No |
| `woocommerce/checkout-contact-information-block` | Contact Information | `remove: true`, `move: true` | No |
| `woocommerce/checkout-shipping-method-block` | Delivery | `remove: true`, `move: true` | No |
| `woocommerce/checkout-pickup-options-block` | Pickup Method | `remove: true`, `move: true` | No |
| `woocommerce/checkout-shipping-address-block` | Shipping Address | `remove: true`, `move: true` | No |
| `woocommerce/checkout-billing-address-block` | Billing Address | `remove: true`, `move: true` | No |
| `woocommerce/checkout-shipping-methods-block` | Shipping Options | `remove: true`, `move: true` | No |
| `woocommerce/checkout-payment-block` | Payment Options | `remove: true`, `move: true` | No |
| `woocommerce/checkout-actions-block` | Actions | `remove: true`, `move: true` | No |
| `woocommerce/checkout-order-summary-block` | Order Summary | `remove: true`, `move: true` | No |
| `woocommerce/checkout-order-summary-subtotal-block` | Subtotal | `remove: true`, `move: true` | No |
| `woocommerce/checkout-order-summary-discount-block` | Discount | `remove: true`, `move: true` | No |
| `woocommerce/checkout-order-summary-fee-block` | Fees | `remove: true`, `move: true` | No |
| `woocommerce/checkout-order-summary-shipping-block` | Shipping | `remove: true`, `move: true` | No |
| `woocommerce/checkout-order-summary-taxes-block` | Taxes | `remove: true`, `move: true` | No |

## Mini-Cart inner blocks (`woocommerce/mini-cart-contents`)

The top-level `woocommerce/mini-cart` button block itself is unlocked (`supports.lock` enabled, no default `lock` attribute), while the slide-out drawer template part renders `woocommerce/mini-cart-contents`.

### Unlocked and removable

| Block name | Title | Default `lock` (`remove` / `move`) | User can toggle lock (`supports.lock`) | Reason |
| --- | --- | --- | --- | --- |
| `woocommerce/mini-cart-cart-button-block` | Mini-Cart View Cart Button | `remove: false`, `move: false` | Yes | Optional if directing shoppers straight to Checkout |
| `woocommerce/mini-cart-checkout-button-block` | Mini-Cart Proceed to Checkout Button | `remove: false`, `move: false` | Yes | Optional if directing shoppers to the Cart page first |
| `woocommerce/mini-cart-shopping-button-block` | Mini-Cart Shopping Button | `remove: false`, `move: false` | Yes | Optional CTA button shown in the empty Mini-Cart state |

### Structural, non-removable, or container-locked

| Block name | Title | Locking mechanism | User can toggle lock (`supports.lock`) |
| --- | --- | --- | --- |
| `woocommerce/mini-cart-contents` | Mini-Cart Contents | `remove: true`, `move: true` | No |
| `woocommerce/empty-mini-cart-contents-block` | Empty Mini-Cart view | `remove: true`, `move: true` | No |
| `woocommerce/filled-mini-cart-contents-block` | Filled Mini-Cart view | `remove: true`, `move: true` | No |
| `woocommerce/mini-cart-title-block` | Mini-Cart Title | `remove: true`, `move: true` (`templateLock="all"` for children) | No |
| `woocommerce/mini-cart-title-label-block` | Mini-Cart Title Label | Locked by parent `woocommerce/mini-cart-title-block` (`templateLock="all"`) | No |
| `woocommerce/mini-cart-title-items-counter-block` | Mini-Cart Title Items Counter | Locked by parent `woocommerce/mini-cart-title-block` (`templateLock="all"`) | No |
| `woocommerce/mini-cart-items-block` | Mini-Cart Items | `remove: true`, `move: true` | No |
| `woocommerce/mini-cart-products-table-block` | Mini-Cart Products Table | `remove: true`, `move: false` | No |
| `woocommerce/mini-cart-footer-block` | Mini-Cart Footer | `remove: true`, `move: true` | No |
