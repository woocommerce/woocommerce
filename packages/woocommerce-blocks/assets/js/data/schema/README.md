# Schema Store

To utilize this store you will import the `SCHEMA_STORE_KEY` in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the key via:

```js
import { SCHEMA_STORE_KEY } from '@woocommerce/block-data';
```
##  Actions

The following actions are used for dispatching data to this store state.

> **Note:** You should rarely need to use any of these actions directly as they are mostly used internally by the resolvers.

### `receiveRoutes( routes, namespace = '/wc/blocks' )`

This returns an action object used to update the store with the provided list of resource routes.

| Argument    | Type   | Description                                                                                                                                         |
| ----------- | ------ | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| `routes`    | Array  | This should be an array of routes attached for the given namespace. Eg. `[ '/wc/blocks/products', '/wc/blocks/products/attributes/(?P<id>[\d]+)' ]` |
| `namespace` | string | The namespace the routes belong to (eg. `/wc/blocks`)                                                                                               |

## Selectors

### `getRoute( namespace, resourceName, ids = [] )`

This is used for retrieving a route for the given namespace, resource name and (if necessary) ids.

Example:  If you are looking for a route for a single product on the `wc/blocks` namespace, then you'd have `[20]` as the ids.

```js
// '/wc/blocks/products/20'
wp.data.select( SCHEMA_STORE_KEY ).getRoute( '/wc/blocks', 'products', [20] );
```
| Argument    | Type   | Description                                                    |
| ----------- | ------ | -------------------------------------------------------------- |
| `namespace` | string | Namespace for the route (eg. `/wc/blocks`)                     |
| `resourceName` | string | The resource name for the route (eg. `products/attributes/terms`) |
| `ids`       | array  | Only needed if the route has placeholders for ids.             |

### `getRoutes( namespace )`

This will return all the registered routes for the given namespace as a flat array.
