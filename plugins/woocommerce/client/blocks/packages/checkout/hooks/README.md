# Checkout Hooks <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [`useValidateCheckout`](#usevalidatecheckout)
    -   [Usage](#usage)
    -   [Return Value](#return-value)

React hooks for checkout functionality.

## `useValidateCheckout`

A hook that validates the checkout form and automatically scrolls to the first validation error if any are found.

This hook is primarily used internally by the `PlaceOrderButton` component to provide the `validate` prop to custom place order button components. However, it can also be used directly if needed.

### Usage

```jsx
// Aliased import
import { useValidateCheckout } from '@woocommerce/blocks-checkout';

// Global import
// const { useValidateCheckout } = wc.blocksCheckout;

const MyComponent = () => {
	const validateCheckout = useValidateCheckout();

	const handleClick = async () => {
		const { hasError } = await validateCheckout();

		if ( hasError ) {
			// Validation failed - errors are automatically shown and
			// the page scrolls to the first error
			return;
		}

		// Validation passed - proceed with your logic
	};

	return <button onClick={ handleClick }>Validate</button>;
};
```

### Return Value

The hook returns a function that, when called:

1. Emits the `CHECKOUT_VALIDATION` event to run all registered validation callbacks
2. Checks the validation store for any field-level validation errors
3. If errors are found:
    - Shows all validation errors
    - Scrolls to and focuses the first error element
4. Returns a promise that resolves to `{ hasError: boolean }`

| Property   | Type      | Description                                     |
| :--------- | :-------- | :---------------------------------------------- |
| `hasError` | `boolean` | `true` if validation failed, `false` if passed. |

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&template=suggestion-for-documentation-improvement-correction.md&title=Feedback%20on%20./packages/checkout/hooks/README.md)

<!-- /FEEDBACK -->
