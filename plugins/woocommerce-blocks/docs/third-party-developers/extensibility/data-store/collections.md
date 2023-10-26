# Collections Store (`wc/store/collections`) <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Overview](#overview)
-   [Usage](#usage)
-   [Actions](#actions)
    -   [receiveCollection( namespace, resourceName, queryString, ids = \[\], items = \[\], replace = false )](#receivecollection-namespace-resourcename-querystring-ids---items---replace--false-)
-   [Selectors](#selectors)
    -   [getCollection](#getcollection)
    -   [getCollectionHeader](#getcollectionheader)

## Overview

The Collections Store allows to retrieve product-related collections within WooCommerce Blocks.

## Usage

To utilize this store you will import the COLLECTIONS_STORE_KEY in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the key via:

```js
import { COLLECTIONS_STORE_KEY } from '@woocommerce/block-data';
```

## Actions

### receiveCollection( namespace, resourceName, queryString, ids = [], items = [], replace = false )

This will return an action object for the given arguments used in dispatching the collection results to the store.

> ⚠️ You should rarely have to dispatch this action directly as it is used by the resolver for the `getCollection` selector.

#### _Parameters_ <!-- omit in toc -->

-   _namespace_ `string`: The route namespace for the collection, eg. `/wc/blocks`.
-   _resourceName_ `string`: The resource name for the collection (eg. `products/attributes`.
-   _queryString_ `string`: An additional query string to add to the request for the collection. Note, collections are cached by the query string, eg. `?order=ASC`.
-   _ids_ `array`: If the collection route has placeholders for ids, you provide them via this argument in the order of how the placeholders appear in the route.
-   _response_ `Object`: An object containing a `items` property with the collection items from the response (array), and a `headers` property that is matches the `window.Headers` interface containing the headers from the response.
-   _replace_ `boolean`: Whether or not to replace any existing items in the store for the given indexes (namespace, resourceName, queryString) if there are already values in the store.

## Selectors

### getCollection

This selector will return the collection for the given arguments. It has a sibling resolver, so if the selector has never been resolved, the resolver will make a request to the server for the collection and dispatch results to the store.

#### _Returns_ <!-- omit in toc -->

-   _namespace_ `string`: The route namespace for the collection, eg. `/wc/blocks`.
-   _resourceName_ `string`: The resource name for the collection, eg. `products/attributes`.
-   _query_ `object`: The query arguments for the collection, eg. `{ order: 'ASC', sortBy: Price }`.
-   _ids_ `array`: If the collection route has placeholders for ids you provide the values for those placeholders in this array (in order).

### getCollectionHeader

This selector will return a header from the collection response using the given arguments. It has a sibling resolver that will resolve `getCollection` using the arguments if that has never been resolved.

#### _Returns_ <!-- omit in toc -->

-   `undefined`: If the collection has headers but not a matching header for the given `header` argument, then `undefined` will be returned.

or

-   `null`: If the collection does not have any matching headers for the given arguments, then `null` is returned.

or

-   `object`: If the collection has a matching header for the given arguments, then an object is returned with the following properties:
    -   _namespace_ `string`: The route namespace for the collection, eg. `/wc/blocks`.
    -   _resourceName_ `string`: The resource name for the collection, eg. `products/attributes`.
    -   _header_ `string`: The header key for the header.
    -   _query_ `Object`: The query arguments for the collection, eg. `{ order: 'ASC', sortBy: Price }`.
    -   _ids_ `Array`: If the collection route has placeholders for ids you provide the values for those placeholders in this array (in order).

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/blocks/feature-flags-and-experimental-interfaces.md)

<!-- /FEEDBACK -->
