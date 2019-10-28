This folder contains all the data stores registered with `wp.data` for use by various blocks.  Store keys are exported as constants on the `wc.wcBlocksData` export (external registered as `@woocommerce/block-data` and enqueued via handle `wc-blocks-data-store`).  For any block using the store, make sure you import the store key rather than using the reference directly to ensure dependencies are automatically extracted correctly.

It is assumed there is some familiarity already with interacting with the `wp.data` api. You can read more about that [here](https://github.com/WordPress/gutenberg/tree/master/packages/data).

The following stores are registered:

store | description | store key
------|----------|-------------
[schema](./schema/README.md) | Used for accessing routes. Has more internal usage. | SCHEMA_STORE_KEY
[collections](./collections//README.md) | Holds collections of data indexed by namespace, model name and query string | COLLECTIONS_STORE_KEY
[query-state](./query-state/README.md) | Holds arbitrary values indexed by context and key. Typically used for tracking state of query objects for a given context | QUERY_STATE_STORE_KEY