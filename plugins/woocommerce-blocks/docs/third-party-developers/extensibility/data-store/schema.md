# Schema Store (`wc/store/schema`) <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Overview](#overview)
-   [Usage](#usage)
-   [Actions](#actions)
    -   [receiveRoutes( routes, namespace)](#receiveroutes-routes-namespace)
-   [Selectors](#selectors)
    -   [getRoute( state, namespace, resourceName, ids = \[\] )](#getroute-state-namespace-resourcename-ids---)
    -   [getRoutes( state, namespace )](#getroutes-state-namespace-)
    -   [getRouteFromResourceEntries](#getroutefromresourceentries)
    -   [assembleRouteWithPlaceholders](#assembleroutewithplaceholders)


## Overview

The Schema Store manages the routes associated with WooCommerce Blocks, enabling efficient retrieval and updating of route data for a given namespace. This store streamlines the interaction with resource routes, ensuring that modules can easily access endpoint paths as needed.

## Usage

To utilize this store you will import the `SCHEMA_STORE_KEY` in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the key via:

```js
const { SCHEMA_STORE_KEY } = window.wc.wcBlocksData;
```

## Actions

> ⚠️ You should rarely need to use any of these actions directly as they are mostly used internally by the resolvers.

### receiveRoutes( routes, namespace)

This returns an action object used to update the store with the provided list of resource routes.

#### _Parameters_ <!-- omit in toc -->

-   _routes_ `array`: An array of routes attached for the given namespace, eg. `[ '/wc/blocks/products', '/wc/blocks/products/attributes/(?P<id>[\d]+)' ]`.
-   _namespace_ `string`: The namespace the routes belong to, eg. `/wc/blocks`.

#### _Returns_ <!-- omit in toc -->

-   `object`: An action object used to update the store with the provided list of resource routes with teh following keys:
    -   _type_ `string`: The action type.
    -   _routes_ `object`: An object of routes keyed by the route name.
    -   _namespace_ `string`: The namespace the routes belong to, eg. `/wc/blocks`.

## Selectors

### getRoute( state, namespace, resourceName, ids = [] )

This is used for retrieving a route for the given namespace, resource name and (if necessary) ids.

#### _Parameters_ <!-- omit in toc -->

-   _state_ `object`: The original state.
-   _namespace_ `string`: The namespace for the route, eg. `/wc/blocks`,
-   _resourceName_ `string`: The resource being requested, eg. `products/attributes/terms`.
-   _ids_ `array`: Only needed if the route has placeholders for ids.

#### _Returns_ <!-- omit in toc -->

-   `string`: The route if it is available.

#### _Examples_ <!-- omit in toc -->

If you are looking for a route for a single product on the `wc/blocks` namespace, then you'd have `[ 20 ]` as the ids:

```js
// '/wc/blocks/products/20'
wp.data.select( SCHEMA_STORE_KEY ).getRoute( '/wc/blocks', 'products', [ 20 ] );
```

### getRoutes( state, namespace )

This will return all the registered routes for the given namespace as a flat array.

#### _Parameters_ <!-- omit in toc -->

-   _state_ `object`: The current state.
-   namespace `string`: The namespace to return routes for.

#### _Returns_ <!-- omit in toc -->

-   `array`: An array of all routes for the given namespace.

### getRouteFromResourceEntries

This will returns the route from the given slice of the route state.

#### _Parameters_ <!-- omit in toc -->

-   _stateSlice_ `object`: Slice of the route state from a given namespace and resource name.
-   _ids_ `array` (default: `[]`): An array of id references that are to be replaced in route placeholders.

#### _Returns_ <!-- omit in toc -->

-   `string`: The route for the given resource entries, or an empty string if no route is found.

#### _Example_ <!-- omit in toc -->

```js
const store = select( SCHEMA_STORE_KEY );
const route = store.getRouteFromResourceEntries( stateSlice, ids );
```

### assembleRouteWithPlaceholders

This will return the assembled route with placeholders.

#### _Parameters_ <!-- omit in toc -->

-   _route_ `string`: The route to assemble.
-   _routePlaceholders_ `array`: An array of route placeholders.
-   _ids_ `array`: An array of id references that are to be replaced in route placeholders.

#### _Returns_ <!-- omit in toc -->

-   `string`: The assembled route with placeholders replaced by actual values.

#### _Example_ <!-- omit in toc -->

```js
const store = select( SCHEMA_STORE_KEY );
const route = store.assembleRouteWithPlaceholders( route, routePlaceholders, ids );
```

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/blocks/feature-flags-and-experimental-interfaces.md)

<!-- /FEEDBACK -->
