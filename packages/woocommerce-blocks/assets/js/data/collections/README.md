# Collections Store.

To utilize this store you will import the COLLECTIONS_STORE_KEY in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the key via:

```js
import { COLLECTIONS_STORE_KEY } from '@woocommerce/block-data';
```

## Actions

### `receiveCollection( namespace, resourceName, queryString, ids = [], items = [], replace = false )`

This will return an action object for the given arguments used in dispatching the collection results to the store.

> **Note**: You should rarely have to dispatch this action directly as it is used by the resolver for the `getCollection` selector.

| argument      | type    |  description                                                                                                                                                  |
| ------------- | ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `namespace`   | string  |  The route namespace for the collection (eg. `/wc/blocks`)                                                                                                    |
| `resourceName`   | string  |  The resource name for the collection (eg. `products/attributes`)                                                                                              |
| `queryString` | string  |  An additional query string to add to the request for the collection.  Note, collections are cached by the query string. (eg. '?order=ASC')                   |
| `ids`         | array   |  If the collection route has placeholders for ids, you provide them via this argument in the order of how the placeholders appear in the route                |
| `response`       | Object   |  An object containing a `items` property with the collection items from the response (array), and a `headers` property that is matches the `window.Headers` interface containing the headers from the response. |
| `replace`     | boolean |  Whether or not to replace any existing items in the store for the given indexes (namespace, resourceName, queryString) if there are already values in the store |

## Selectors

### `getCollection( namespace, resourceName, query = null, ids=[] )`

This selector will return the collection for the given arguments. It has a sibling resolver, so if the selector has never been resolved, the resolver will make a request to the server for the collection and dispatch results to the store.

| argument      | type    |  description                                                                                                                                                                                            |
| ------------- | ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `namespace`   | string  |  The route namespace for the collection (eg. `/wc/blocks`)                                                                                                                                              |
| `resourceName`   | string  |  The resource name for the collection (eg. `products/attributes`)                                                                                                                                        |
| `query`       | Object  |  The query arguments for the collection. Eg. `{ order: 'ASC', sortBy: Price }`                                                                                                                          |
| `ids`         | Array   |  If the collection route has placeholders for ids you provide the values for those placeholders in this array (in order).                                                                               |

### `getCollectionHeader( namespace, resourceName, header, query = null, ids = [])`

This selector will return a header from the collection response using the given arguments. It has a sibling resolver that will resolve `getCollection` using the arguments if that has never been resolved.

If the collection has headers but not a matching header for the given `header` argument, then `undefined` will be returned.

If the collection does not have any matching headers for the given arguments, then `null` is returned.

| argument      | type    |  description                                                                                                                                                                                            |
| ------------- | ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `namespace`   | string  |  The route namespace for the collection (eg. `/wc/blocks`)                                                                                                                                              |
| `resourceName`   | string  |  The resource name for the collection (eg. `products/attributes`)                                                                                                                                        |
| `header` | string | The header key for the header. |
| `query`       | Object  |  The query arguments for the collection. Eg. `{ order: 'ASC', sortBy: Price }`                                                                                                                          |
| `ids`         | Array   |  If the collection route has placeholders for ids you provide the values for those placeholders in this array (in order).                                                                               |
