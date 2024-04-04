# Filter Registry <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [registerCheckoutFilters](#registercheckoutfilters)
    -   [Usage](#usage)
    -   [Options](#options)
        -   [`namespace (string)`](#namespace-string)
        -   [`filters (object)`](#filters-object)
-   [applyCheckoutFilter](#applycheckoutfilter)
    -   [Usage](#usage-1)
    -   [Options](#options-1)
        -   [`filterName (string, required)`](#filtername-string-required)
        -   [`defaultValue (mixed, required)`](#defaultvalue-mixed-required)
        -   [`extensions`](#extensions)
        -   [`arg (object)`](#arg-object)
        -   [`validation (function)`](#validation-function)
-   [Available Filters](#available-filters)

The filter registry allows callbacks to be registered to manipulate certain values. This is similar to the traditional filter system in WordPress (where you register a callback with a specific filter and return a modified value).

## registerCheckoutFilters

Registers a callback function with an available filter. This function has the following signature:

```ts
(
  namespace: string,
  filters: Record< string, CheckoutFilterFunction >
)
```

`CheckoutFilterFunction` has this signature:

```ts
type CheckoutFilterFunction = < T >(
	value: T,
	extensions: Record< string, unknown >,
	args?: CheckoutFilterArguments
) => T;
```

### Usage

```js
// Aliased import
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';

// Global import
const { registerCheckoutFilters } = wc.blocksCheckout;

const callback = ( value ) => {
	return value;
};

registerCheckoutFilters( 'my-extension-namespace', {
	filterName: callback,
} );
```

### Options

The following options are available:

#### `namespace (string)`

A string based namespace, usually your extension name, under which to register filters. This should be unique to avoid overwriting existing filters. This ensures that callbacks are only registered once.

#### `filters (object)`

A list of filter names and functions (`CheckoutFilterFunction`) to execute when this filter is applied. When the `CheckoutFilterFunction` is invoked, the following arguments are passed to it:

-   `value` - The value to be filtered.
-   `extensions` A n object containing extension data. If your extension has extended any of the store's API routes, one of the keys of this object will be your extension's namespace. The value will contain any data you add to the endpoint. Each key in the `extensions` object is an extension namespace, so a third party extension cannot interfere with _your_ extension's schema modifications, unless there is a naming collision, so please ensure your extension has a unique namespace that is unlikely to conflict with other extensions.
-   `args` - An object containing any additional data passed to the filter function. This usually (but not always) contains at least a key called `context`. The value of `context` will be (at the moment) either `cart` or `checkout`. This is provided to inform extensions about the exact location that the filter is being applied. The same filter can be applied in multiple places.

## applyCheckoutFilter

This function applies a filter, and all registered callbacks, to a given value.

### Usage

```js
// Aliased import
import { applyCheckoutFilter } from '@woocommerce/blocks-checkout';

// Global import
const { applyCheckoutFilter } = wc.blocksCheckout;

const options = {
	filterName: 'my-filter',
	defaultValue: 'Default Value',
};

const filteredValue = applyCheckoutFilter( options );
```

### Options

The following options are available:

#### `filterName (string, required)`

Name of the filter to apply.

#### `defaultValue (mixed, required)`

Default value to filter. If no filter callbacks are applied, this value will be returned.

#### `extensions`

Some filters pass an object containing extension data returned from the Rest API. This allows extensions to extend the Rest API and retrieve the values.

#### `arg (object)`

Object containing arguments for the filter function.

#### `validation (function)`

A function that needs to return true when the filtered value is passed in order for the filter to be applied.

## Available Filters

Filters are implemented throughout the Mini-Cart, Cart and Checkout Blocks, as well as some components. For a list of filters, [see this document](../../../docs/third-party-developers/extensibility/checkout-block/available-filters.md). You can also search for [usage of `applyCheckoutFilter` within the source code](https://github.com/woocommerce/woocommerce-gutenberg-products-block/search?q=applyCheckoutFilter).

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./packages/checkout/filter-registry/README.md)

<!-- /FEEDBACK -->

