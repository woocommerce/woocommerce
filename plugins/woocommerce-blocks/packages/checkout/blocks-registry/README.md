# WooCommerce Blocks - Blocks Registry <!-- omit in toc -->

This directory contains the Checkout Blocks Registry - functions to register custom blocks that can be inserted into various areas within the Checkout.

**-- These docs will be moved to the main docs directory once finalized --**

The Checkout Block has a function based interface for registering custom Blocks so that merchants can insert them into specific Inner Block areas within the Checkout page layout. Custom Blocks registered in this way can also define a component to render on the frontend in place of the Block.

## Table of Contents <!-- omit in toc -->

- [Registering a block - `registerCheckoutBlock( block, options )`](#registering-a-block---registercheckoutblock-block-options-)
  - [`component` (required)](#component-required)
  - [`areas` (required)](#areas-required)
  - [`configuration`](#configuration)

## Registering a block - `registerCheckoutBlock( block, options )`

To register a checkout block, you use the registerCheckoutBlock function from the checkout blocks registry. An example of importing this for use in your JavaScript file is:

_Aliased import_

```js
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
```

_wc global_

```js
const { registerCheckoutBlock } = wc.blocksCheckout;
```

The register function expects a block name string, and a JavaScript object with options specific to the block you are registering:

```js
registerCheckoutBlock( blockName, options );
```

The options you feed the configuration instance should be an object in this shape (see `CheckoutBlockOptions` typedef):

```js
const options = {
	component: () => <div>A Function Component</div>,
	areas: [ 'areaName' ],
	configuration: {},
};
```

Here's some more details on the _configuration_ options:

### `component` (required)

This is a React component that should replace the Block on the frontend. It will be fed any attributes from the Block and have access to any public context providers in the Checkout context.

You should provide either a _React Component_ or a `React.lazy()` component if you wish to lazy load for performance reasons.

### `areas` (required)

This is an array of string based area names which define when the custom block can be inserted within the Checkout.

See the `RegisteredBlocks` typedef for available areas. Valid values at time of writing include:

-   `totals` - The right side of the checkout containing order totals.
-   `fields` - The left side of the checkout containing checkout form steps.
-   `contactInformation` - Within the contact information form step.
-   `shippingAddress` - Within the shipping address form step.
-   `billingAddress` - Within the billing address form step.
-   `shippingMethods` - Within the shipping methods form step.
-   `paymentMethods` - Within the payment methods form step.

### `configuration`

This is where a standard `BlockConfiguration` should be provided. This matches the [Block Registration Configuration in Gutenberg](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/).

If a `BlockConfiguration` is not provided, your Block Type will not be registered in WordPress unless you manually register the block using `registerBlockType` (this is what `registerCheckoutBlock` does for you behind the scenes).
