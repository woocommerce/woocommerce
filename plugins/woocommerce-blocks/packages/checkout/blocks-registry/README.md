# WooCommerce Blocks - Blocks Registry <!-- omit in toc -->

This directory contains the Checkout Blocks Registry - functions to register custom blocks that can be inserted into various areas within the Checkout.

**-- These docs will be moved to the main docs directory once finalized --**

The Checkout Block has a function based interface for registering custom Blocks so that merchants can insert them into specific Inner Block areas within the Checkout page layout. Custom Blocks registered in this way can also define a component to render on the frontend in place of the Block.

## Table of Contents <!-- omit in toc -->

- [Registering a block](#registering-a-block)
- [Registering a block component - `registerCheckoutBlock( options )`](#registering-a-block-component---registercheckoutblock-options-)
  - [`metadata` (required)](#metadata-required)
  - [`component` (required)](#component-required)

## Registering a block

To register a checkout block, first, register your Block Type with WordPress using https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/. We recommend using the blocks.json method to avoid
repetition.

When registering your block, you should:

1. Define the `parent` property to include a list of areas where your block will be available. See the `innerBlockAreas` typedef for available areas. Valid values at time of writing include:

-   `woocommerce/checkout-totals-block` - The right side of the checkout containing order totals.
-   `woocommerce/checkout-fields-block` - The left side of the checkout containing checkout form steps.
-   `woocommerce/checkout-contact-information-block` - Within the contact information form step.
-   `woocommerce/checkout-shipping-address-block` - Within the shipping address form step.
-   `woocommerce/checkout-billing-address-block` - Within the billing address form step.
-   `woocommerce/checkout-shipping-methods-block` - Within the shipping methods form step.
-   `woocommerce/checkout-payment-methods-block` - Within the payment methods form step.

## Registering a block component - `registerCheckoutBlock( options )`

After registering your block, you need to define which component will replace your block on the frontend. To do this, use the registerCheckoutBlock function from the checkout blocks registry. An example of importing this for use in your JavaScript file is:

_Aliased import_

```js
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
```

_wc global_

```js
const { registerCheckoutBlock } = wc.blocksCheckout;
```

The register function expects a JavaScript object with options specific to the block you are registering:

```js
registerCheckoutBlock( options );
```

The options you feed the configuration instance should be an object in this shape (see `CheckoutBlockOptions` typedef):

```js
const options = {
	metadata: {
		name: 'namespace/block-name',
		parent: [ 'woocommerce/checkout-totals-block' ],
	},
	component: () => <div>A Function Component</div>,
};
```

Here's some more details on the _configuration_ options:

### `metadata` (required)

This is a your blocks metadata (from blocks.json). It needs to define at least a `name` (block name), and `parent` (the areas on checkout) to be valid.

### `component` (required)

This is a React component that should replace the Block on the frontend. It will be fed any attributes from the Block and have access to any public context providers in the Checkout context.

You should provide either a _React Component_ or a `React.lazy()` component if you wish to lazy load for performance reasons.
