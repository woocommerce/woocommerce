# Frontend notices <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Notices in WooCommerce Blocks](#notices-in-woocommerce-blocks)
    -   [`StoreNoticesContainer`](#storenoticescontainer)
-   [Snackbar notices in WooCommerce Blocks](#snackbar-notices-in-woocommerce-blocks)

## Notices in WooCommerce Blocks

WooCommerce Blocks uses the [`@wordpress/notices`](https://github.com/WordPress/gutenberg/blob/d9eb36d80e05b4e45b1ad8462c8bace4e9cf1f6f/docs/reference-guides/data/data-core-notices.md) package to display notices in the frontend. For more information on the actions and selectors available on this data store, please review [the `@wordpress/notices` documentation](https://github.com/WordPress/gutenberg/blob/d9eb36d80e05b4e45b1ad8462c8bace4e9cf1f6f/docs/reference-guides/data/data-core-notices.md)

### `StoreNoticesContainer`

To display notices of a certain context, use the `StoreNoticesContainer` and pass the context as a prop to it.

The below example will show all notices with type `default` that are in the `wc/cart` context. If no context prop is passed, then the `default` context will be used.

On the Cart Block, a `StoreNoticesContainer` is already rendered with the `wc/cart` context, and on the Checkout Block, a `StoreNoticesContainer` is already rendered with the `wc/checkout` context. To display errors from other contexts, you can use the `StoreNoticesContainer` component with context passed as a prop.

`StoreNoticesContainer` also support passing an array of context strings to it, this allows you to capture several contexts at once, while filtering out similar notices.

#### Single context

```jsx
import { StoreNoticesContainer } from '@woocommerce/blocks-checkout';

const PaymentErrors = () => {
	return <StoreNoticesContainer context="wc/payment" />;
};
```

#### Multiple contexts

```jsx
import { StoreNoticesContainer } from '@woocommerce/blocks-checkout';

const AddressForm = () => {
	return (
		<StoreNoticesContainer
			context={ [
				'wc/checkout/shipping-address',
				'wc/checkout/billing-address',
			] }
		/>
	);
};
```

## Snackbar notices in WooCommerce Blocks

WooCommerce Blocks also shows snackbar notices, to add a snackbar notice you need to create a notice with `type:snackbar` in the options object.

```js
import { dispatch } from '@wordpress/data';

dispatch( 'core/notices' ).createNotice(
	'snackbar',
	'This is a snackbar notice',
	{
		type: 'snackbar',
		actions: [
			{
				label: 'Dismiss',
				onClick: () => {
					dispatch( 'core/notices' ).removeNotice(
						'snackbar-notice-id'
					);
				},
			},
		],
	},
	'snackbar-notice-id'
);
```
