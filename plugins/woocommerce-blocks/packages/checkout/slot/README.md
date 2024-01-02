# Slot and Fill <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [`createSlotFill( slotName )`](#createslotfill-slotname-)
    -   [Usage](#usage)
    -   [Options](#options)
        -   [`slotName (string, required)`](#slotname-string-required)
        -   [`onError (Function)`](#onerror-function)
    -   [`Slot` Component](#slot-component)
        -   [Usage](#usage-1)
        -   [Options](#options-1)
            -   [`as (string|element)`](#as-stringelement)
            -   [`className (string)`](#classname-string)
            -   [`fillProps (object)`](#fillprops-object)
        -   [`Fill` Component](#fill-component)
-   [Extending Checkout via Slot Fills](#extending-checkout-via-slot-fills)
-   [Available Slot Fills](#available-slot-fills)

Slot and Fill are a pair of components which enable developers to render elsewhere in a React element tree, a pattern often referred to as "portal" rendering. It is a pattern for component extensibility, where a single Slot may be occupied by an indeterminate number of Fills elsewhere in the application.

This module is an abstraction on top of the Slot and Fill implementation in WordPress and is meant to be used internally, therefore the documentation only touches the abstraction part. You can read more about Slot and Fill in [@wordpress/components documentation](https://github.com/WordPress/gutenberg/tree/c53d26ea79bdcb1a3007a994078e1fc9e0195466/packages/components/src/slot-fill).

## `createSlotFill( slotName )`

Calling `createSlotFill` with a `slotName` returns two components: `Slot` and `Fill`. Slots are implemented in WooCommerce Blocks, while Fills can be used by extensions to add content within the Slot. A Slot must be called in a tree that has `SlotFillProvider` in it.

### Usage

```jsx
// Aliased import
import { createSlotFill } from '@woocommerce/blocks-checkout';

// Global import
// const { createSlotFill } = wc.blocksCheckout;

const slotName = '__experimentalSlotName';

const { Fill, Slot } = createSlotFill( slotName );
```

### Options

`createSlotFill` accepts the following options:

#### `slotName (string, required)`

The name of slot to be created.

#### `onError (Function)`

If a `Fill` causes an error, and the current user is an admin user, this function will be called. You can customize the error shown to admins by passing `onError` to `createSlotFill`.

```jsx
// Aliased import
import { createSlotFill } from '@woocommerce/blocks-checkout';

// Global import
// const { createSlotFill } = wc.blocksCheckout;

const slotName = '__experimentalSlotName';

const onError = ( errorMessage ) => {
	return (
		<div className="my-custom-error">
			You got an error! <br />
			{ errorMessage }
			Contact support at{ ' ' }
			<a href="mailto:help@example.com">help@example.com</a>
		</div>
	);
};

const { Fill, Slot } = createSlotFill( slotName, onError );
```

### `Slot` Component

`createSlotFill` returns a `Slot` component. This is rendered in the app and will render any Fills within it.

#### Usage

```jsx
// Aliased import
import { createSlotFill } from '@woocommerce/blocks-checkout';

// Global import
// const { createSlotFill } = wc.blocksCheckout;

const slotName = '__experimentalSlotName';

const { Fill: FillComponent, Slot: SlotComponent } = createSlotFill( slotName );

const Slot = ( { className } ) => {
	return <SlotComponent className={ 'my-slot-component' } />;
};

// Assign your Slot to your Fill.
FillComponent.Slot = Slot;

export default FillComponent;
```

#### Options

`Slot` accepts the following options:

##### `as (string|element)`

Element used to render the slot. By default, `Slot` would render a `div`, but you can customize what gets rendered instead.

##### `className (string)`

A class name applied to the rendered element.

##### `fillProps (object)`

Props passed to each fill implementation.

```jsx
// Aliased import
import { createSlotFill } from '@woocommerce/blocks-checkout';

// Global import
// const { createSlotFill } = wc.blocksCheckout;

const slotName = '__experimentalSlotName';

const { Fill: FillComponent, Slot: SlotComponent } = createSlotFill(
	slotName
);

const Slot = ( { className } ) => {
	return (
		<SlotComponent
			className={ 'my-slot-component' }
			fillProps={ { // ...custom data goes here and is passed to all fills } }
		/>
	);
};

// Assign your Slot to your Fill.
FillComponent.Slot = Slot;

export default FillComponent;
```

#### `Fill` Component

Each `Fill` receives any `fillProps` from the `Slot`, and also renders an [errorBoundary](https://reactjs.org/docs/error-boundaries.html) inside of it. This catches broken fills and prevents them from breaking other fills.

## Extending Checkout via Slot Fills

Slot/Fills are exported and available for use by extensions. One such Slot Fill is `ExperimentalOrderMeta` [exported from here](../components/order-meta/index.js). This provides the Slot, and within it, you can define your fill:

```jsx
import { registerPlugin } from '@wordpress/plugins';

// Aliased import
import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';

// Global import
// const { ExperimentalOrderMeta } = wc.blocksCheckout;

// extensions and cartData are both fillProps.
const MyComponent = ( { extensions, cartData } ) => {
	const { myPlugin } = extensions;
	return <Meta data={ myPlugin } />;
};

const render = () => {
	return (
		<ExperimentalOrderMeta>
			<MyComponent />
		</ExperimentalOrderMeta>
	);
};

registerPlugin( 'my-plugin', { render } );
```

## Available Slot Fills

Slot Fills are implemented throughout the Cart and Checkout Blocks, as well as some components. For a list of available Slot Fills, [see this document](../../../docs/third-party-developers/extensibility/checkout-block/available-slot-fills.md).

<!-- FEEDBACK -->

---

[We're hiring!](https://woo.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./packages/checkout/slot/README.md)

<!-- /FEEDBACK -->

