# Slot Fill

Slot and Fill are a pair of components which enable developers to render elsewhere in a React element tree, a pattern often referred to as "portal" rendering. It is a pattern for component extensibility, where a single Slot may be occupied by an indeterminate number of Fills elsewhere in the application.

Read more about Slot Fill in [@wordpress/components documentation](https://github.com/WordPress/gutenberg/tree/c53d26ea79bdcb1a3007a994078e1fc9e0195466/packages/components/src/slot-fill).

This file is an abstraction above Gutenberg's implementation and is meant to be used internally, therefor, the documentation only touches the abstraction part.

## Usage

Calling `createSlotFill` with a `slotName` would give you a couple of components: `Slot` and `Fill`. 3PD would use Fill, and you will use `Slot` inside your code. A Slot must be called in a tree that has `SlotFillProvider` in it.

**Always** prefix your `slotName` with `__experimental` and your `Fill` with `Experimental` until you decide to publicly announce them.

Assign your Slot to your Fill `ExperimentalOrderMeta.Slot = Slot`.

If you need to pass extra data from the Slot to the Fill, use `fillProps`.

```jsx
import { createSlotFill } from '@woocommerce/blocks-checkout';

const slotName = '__experimentalOrderMeta';

const { Fill: ExperimentalOrderMeta, Slot: OrderMetaSlot } = createSlotFill(
	slotName
);

const Slot = ( { className } ) => {
	const { extensions, cartData } = useStoreCart();
	return (
		<OrderMetaSlot
			className={ classnames(
				className,
				'wc-block-components-order-meta'
			) }
			fillProps={ { extensions, cartData } }
		/>
	);
};

ExperimentalOrderMeta.Slot = Slot;

export default ExperimentalOrderMeta;
```

`Fill` renders an [errorBoundary](https://reactjs.org/docs/error-boundaries.html) inside of it, this is meant to catch broken fills and preventing them from breaking code or other fills.
If the current user is an admin, the error would be shown instead of the components.
Otherwise, nothing would be shown and the fill would be removed.
You can customize the error shown to admins by passing `onError` to `createSlotFill`.

```jsx
import { createSlotFill } from '@woocommerce/blocks-checkout';

const slotName = '__experimentalOrderMeta';

const onError = ( errorMessage ) => {
	return (
		<div className="my-custom-error">
		You got an error! <br />
		{errorMessage}
		Contact support at <a href="mailto:help@example.com">help@example.com</a>
		</div>
	)
}
const { Fill: ExperimentalOrderMeta, Slot: OrderMetaSlot } = createSlotFill(
	slotName, onError
);
```

You can pass props to the fills to be used.

```jsx
import { createSlotFill } from '@woocommerce/blocks-checkout';

const slotName = '__experimentalOrderMeta';

const { Fill: ExperimentalOrderMeta, Slot: OrderMetaSlot } = createSlotFill( slotName );

const Slot = () => {
	const { extensions, cartData } = useStoreCart();
	return <OrderMetaSlot fillProps={ { extensions, cartData } } />
}

ExperimentalOrderMeta.Slot = Slot;

export default ExperimentalOrderMeta;
```

```jsx
import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';

const MyComponent = ( { extensions, cartData } ) => {
	const { myPlugin } = extensions;
	return <Meta data={myPlugin} />
}

const render = () => {
	return <ExperimentalOrderMeta><MyComponent /></ExperimentalOrderMeta>
}

registerPlugin( 'my-plugin', { render } );
```
## Props

`Slot` accepts several props to customize it.

### as

By default, `Slot` would render a div inside your DOM, you can customize what gets rendered instead.
- Type: `String|Element`
- Required: No

### className

The rendered element can accept a className.
- Type: `String`
- Required: No

### fillProps

Props passed to each fill implementation.
- Type: `Object`
- Required: No

`createSlotFill` accepts a couple of props.

### slotName

The name of slot to be created.
- Type: `String`
- Required: Yes

### onError

A function returns an element to be rendered if the current is an admin and an error is caught, accepts `errorMessage` as a param that is the formatted error.
- Type: `Function`
- Required: No