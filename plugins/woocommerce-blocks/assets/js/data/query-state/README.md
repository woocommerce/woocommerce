# Query State Store <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

- [Actions](#actions)
    - [`setQueryValue( context, queryKey, value )`](#setqueryvalue-context-querykey-value-)
    - [`setValueForQueryContext( context, value )`](#setvalueforquerycontext-context-value-)

To utilize this store you will import the `QUERY_STATE_STORE_KEY` in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the key via:

```js
import { QUERY_STATE_STORE_KEY } from '@woocommerce/block-data';
```

## Actions

The following actions are used for dispatching data to this store state.

> **Note:**: New values will always overwrite any existing entry in the store.

### `setQueryValue( context, queryKey, value )`

This will set a single query-state value for a given context.

| Argument   | Type   | Description                                                                                                             |
| ---------- | ------ | ----------------------------------------------------------------------------------------------------------------------- |
| `context`  | string | The context for the query state being stored (eg. might be a block name so you can keep query-state specific per block) |
| `queryKey` | string | The reference for the value being stored.                                                                               |
| `value`    | mixed  | The actual value being stored for the query-state.                                                                      |

### `setValueForQueryContext( context, value )`

This will set the query-state for a given context. Typically this is used to set/replace the entire query-state for a given context rather than the individual keys for the context via `setQueryValue`.

| Argument  | Type   | Description                                                                                                             |
| --------- | ------ | ----------------------------------------------------------------------------------------------------------------------- |
| `context` | string | The context for the query state being stored (eg. might be a block name so you can keep query-state specific per block) |
| `value`   | Object | An object of key/value pairs for the query state being attached to the context.                                         |

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/blocks/feature-flags-and-experimental-interfaces.md)

<!-- /FEEDBACK -->
