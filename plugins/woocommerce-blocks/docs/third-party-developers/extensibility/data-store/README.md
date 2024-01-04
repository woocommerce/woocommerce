# Data Store

This document provides an overview of the data stores registered with `wp.data` for use by various blocks. Store keys are exported as constants on the `wc.wcBlocksData` export (externally registered as `@woocommerce/block-data` and enqueued via handle `wc-blocks-data-store`). For any block using the store, it's recommended to import the store key rather than using the reference directly to ensure dependencies are extracted correctly. It is assumed readers have some familiarity with the `wp.data` API. You can read more about that [here](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/).

## [Cart Store (wc/store/cart)](cart.md)

The **[Cart Store (wc/store/cart)](cart.md)** is responsible for handling cart-related data and operations. To access this store using a constant, you can use:

```ts
const { CART_STORE_KEY } = window.wc.wcBlocksData;
```

## [Checkout Store (wc/store/checkout)](checkout.md)

The **[Checkout Store (wc/store/checkout)](checkout.md)** manages the checkout process, ensuring a seamless experience for users. To access this store using a constant, you can use:

```ts
const { CHECKOUT_STORE_KEY } = window.wc.wcBlocksData;
```

## [Collections Store (wc/store/collections)](collections.md)

The **[Collections Store (wc/store/collections)](collections.md)** holds data indexed by namespace, model name, and query string. To access this store using a constant, you can use:

```ts
const { COLLECTIONS_STORE_KEY } = window.wc.wcBlocksData;
```

## [Payment Store (wc/store/payment)](payment.md)

The **[Payment Store (wc/store/payment)](payment.md)** deals with all payment-related data and transactions. To access this store using a constant, you can use:

```ts
const { PAYMENT_STORE_KEY } = window.wc.wcBlocksData;
```

## [Query-State Store (wc/store/query-state)](query-state.md)

The **[Query-State Store (wc/store/query-state)](query-state.md)** holds arbitrary values indexed by context and key. It's often used for tracking the state of query objects for a given context. To access this store using a constant, you can use:

```ts
const { QUERY_STATE_STORE_KEY } = window.wc.wcBlocksData;
```

## [Schema Store (wc/store/schema)](schema.md)

The **[Schema Store (wc/store/schema)](schema.md)** is primarily used for accessing routes and has more of an internal usage. To access this store using a constant, you can use:

```ts
const { SCHEMA_STORE_KEY } = window.wc.wcBlocksData;
```

## [Store Notices Store (wc/store/store-notices)](store-notices.md)

The **[Store Notices Store (wc/store/store-notices)](store-notices.md)** is dedicated to handling various store notices and alerts. To access this store using a constant, you can use:

```ts
const { STORE_NOTICES_STORE_KEY } = window.wc.wcBlocksData;
```

## [Validation Store (wc/store/validation)](validation.md)

The **[Validation Store (wc/store/validation)](validation.md)** holds data relating to validation errors, it is primarily used in the Cart and Checkout flows to ensure the Checkout doesn't continue while invalid data is present. To access this store using a constant, you can use:

```ts
const { VALIDATION_STORE_KEY } = window.wc.wcBlocksData;
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woo.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/data-store/README.md)

<!-- /FEEDBACK -->
