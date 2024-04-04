# Validation Store (`wc/store/validation`) <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Overview](#overview)- [Overview](#overview)
-   [Usage](#usage)
-   [Example](#example)
-   [Actions](#actions)
    -   [clearValidationError( errorId )](#clearvalidationerror-errorid-)
    -   [clearValidationErrors( errors )](#clearvalidationerrors-errors-)
    -   [setValidationErrors( errors )](#setvalidationerrors-errors-)
    -   [hideValidationError( errorId )](#hidevalidationerror-errorid-)
    -   [showValidationError( errorId )](#showvalidationerror-errorid-)
    -   [showAllValidationErrors](#showallvalidationerrors)
    -   [clearAllValidationErrors](#clearallvalidationerrors)
-   [Selectors](#selectors)
    -   [getValidationError( errorId )](#getvalidationerror-errorid-)
    -   [getValidationErrorId( errorId )](#getvalidationerrorid-errorid-)
    -   [hasValidationErrors](#hasvalidationerrors)

## Overview

The validation data store provides a way to show errors for fields in the Cart or Checkout blocks.

The data in the store should be a single object, the keys of which are the _error IDs_ and values are the data associated with that error message. The values in the object should contain _message_ and _hidden_. The _message_ is the error message to display and _hidden_ is a boolean indicating whether the error should be shown or not.

An example of how the data should be structured:

```js
{
    "error-id-1": {
        message: "This is an error message",
        hidden: false,
    },
    "error-id-2": {
        message: "This is another error message",
        hidden: true,
    },
}
```

When the checkout process begins, it will check if this data store has any entries, and if so, it will stop the checkout process from proceeding. It will also show any errors that are hidden. Setting an error to hidden will not clear it from the data store!

## Usage

To utilize this store you will import the `VALIDATION_STORE_KEY` in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the key via:

```js
const { VALIDATION_STORE_KEY } = window.wc.wcBlocksData;
```

## Example

To better understand the Validation Store, let's use the required checkbox of the Terms and Conditions as an example. In the page editor, a merchant can define that a buyer must agree to the Terms and Conditions by making the checkbox required.

![image](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-24-at-17.22.45.png)

In WooCommerce Blocks, we're using a `useEffect` hook to check if the checkbox is required and if it is checked. If the checkbox is required and not checked, we're adding a validation error to the store. If the checkbox is required and checked, we're clearing the validation error from the store.

```ts
useEffect( () => {
	if ( ! checkbox ) {
		return;
	}
	if ( checked ) {
		clearValidationError( validationErrorId );
	} else {
		setValidationErrors( {
			[ validationErrorId ]: {
				message: __(
					'Please read and accept the terms and conditions.',
					'woo-gutenberg-products-block'
				),
				hidden: true,
			},
		} );
	}
	return () => {
		clearValidationError( validationErrorId );
	};
}, [
	checkbox,
	checked,
	validationErrorId,
	clearValidationError,
	setValidationErrors,
] );
```

By default, the validation error is hidden. This is because we don't want to show the error message until the buyer has tried to submit the form. Before submitting the checkout form, the validation message can already be seen in the Validation Store.

![image](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-24-at-17.28.56.png)

When a buyer submits the checkout form without checking the Terms and Conditions checkbox, the entry `hidden: true` will be changed to `hidden: false` and the validation message is shown.

![image](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-24-at-17.33.01.png)

In WooCommerce Blocks, we're checking if text input fields have a validation error using the following code:

```ts
const hasError = validationError?.message && ! validationError?.hidden;
```

> 💡 The main point to remember from this example is:
>
> -   `hidden: true` means there's a validation error, but it's kept from the user's view.
> -   `hidden: false` indicates that the validation error is actively being shown to the user.

In the example above, the `message` is hidden and only the text color is changed to red, highlighting that this field has a validation error.

In some cases, it's desired to show the validation error message to the user. For example, if the buyer tries to submit the checkout form without filling in the required fields. An example can seen when leaving the first name, last name and address fileds empty:

![image](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-25-at-18.28.30.png)

In WooCommerce Blocks, the following function handles the display logic of the validation error message:

```ts
export const ValidationInputError = ( {
	errorMessage = '',
	propertyName = '',
	elementId = '',
}: ValidationInputErrorProps ): JSX.Element | null => {
	const { validationError, validationErrorId } = useSelect( ( select ) => {
		const store = select( VALIDATION_STORE_KEY );
		return {
			validationError: store.getValidationError( propertyName ),
			validationErrorId: store.getValidationErrorId( elementId ),
		};
	} );

	if ( ! errorMessage || typeof errorMessage !== 'string' ) {
		if ( validationError?.message && ! validationError?.hidden ) {
			errorMessage = validationError.message;
		} else {
			return null;
		}
	}

	return (
		<div className="wc-block-components-validation-error" role="alert">
			<p id={ validationErrorId }>{ errorMessage }</p>
		</div>
	);
};
```

A simplified version of the code snippet above would be the following:

```js
{
	validationError?.hidden === false && (
		<div className="wc-block-components-validation-error" role="alert">
			<p>{ validationError?.message }</p>
		</div>
	);
}
```

## Actions

### clearValidationError( errorId )

Clears a validation error.

#### _Parameters_ <!-- omit in toc -->

-   _errorId_ `string`: The error ID to clear validation errors for.

#### _Example_ <!-- omit in toc -->

```js
const store = dispatch( VALIDATION_STORE_KEY );
store.clearValidationError( 'billing-first-name' );
```

### clearValidationErrors( errors )

Clears multiple validation errors at once. If no error IDs are passed, all validation errors will be cleared.

#### _Parameters_ <!-- omit in toc -->

-   _errors_ `string[]` or `undefined`: The error IDs to clear validation errors for. This can be undefined, and if it is, all validation errors will be cleared.

#### _Example_ <!-- omit in toc -->

1. This will clear only the validation errors passed in the array.

```js
const store = dispatch( VALIDATION_STORE_KEY );
store.clearValidationErrors( [
	'billing-first-name',
	'billing-last-name',
	'terms-and-conditions',
] );
```

2. This will clear all validation errors.

```js
const store = dispatch( VALIDATION_STORE_KEY );
store.clearValidationErrors();
```

### setValidationErrors( errors )

Sets the validation errors. The entries in _errors_ will be _added_ to the list of validation errors. Any entries that already exist in the list will be _updated_ with the new values.

#### _Parameters_ <!-- omit in toc -->

-   _errors_ `object`: The new validation errors, the keys of the object are the validation error IDs, and the values should be objects containing _message_ `string` and _hidden_ `boolean`.

#### _Example_ <!-- omit in toc -->

```js
const { dispatch } = wp.data;
const { setValidationErrors } = dispatch( VALIDATION_STORE_KEY );

setValidationErrors( {
	'billing-first-name': {
		message: 'First name is required.',
		hidden: false,
	},
	'billing-last-name': {
		message: 'Last name is required.',
		hidden: false,
	},
} );
```

### hideValidationError( errorId )

Hides a validation error by setting the `hidden` property to `true`. This will _not_ clear it from the data store!

#### _Parameters_ <!-- omit in toc -->

-   _errorId_ `string`: The error ID to hide.

#### _Example_ <!-- omit in toc -->

```js
const { dispatch } = wp.data;
const { hideValidationError } = dispatch( VALIDATION_STORE_KEY );

hideValidationError( 'billing-first-name' );
```

### showValidationError( errorId )

Shows a validation error by setting the `hidden` property to `false`.

#### _Parameters_ <!-- omit in toc -->

-   _errorId_ `string`: The error ID to show.

#### _Example_ <!-- omit in toc -->

```js
const { dispatch } = wp.data;
const { showValidationError } = dispatch( VALIDATION_STORE_KEY );

showValidationError( 'billing-first-name' );
```

### showAllValidationErrors

Shows all validation errors by setting the `hidden` property to `false`.

#### _Example_ <!-- omit in toc -->

```js
const { dispatch } = wp.data;
const { showAllValidationErrors } = dispatch( VALIDATION_STORE_KEY );

showAllValidationErrors();
```

### clearAllValidationErrors

Clears all validation errors by removing them from the store.

#### _Example_ <!-- omit in toc -->

```js
const { clearAllValidationErrors } = dispatch( VALIDATION_STORE_KEY );
clearAllValidationErrors();
```

## Selectors

### getValidationError( errorId )

Returns the validation error.

#### _Parameters_ <!-- omit in toc -->

-   _errorId_ `string`: The error ID to get validation errors for.

#### _Returns_ <!-- omit in toc -->

-   `object`: The validation error which is an object containing _message_ `string` and _hidden_ `boolean`.

#### _Example_ <!-- omit in toc -->

```js
const store = select( VALIDATION_STORE_KEY );
const billingFirstNameError = store.getValidationError( 'billing-first-name' );
```

### getValidationErrorId( errorId )

Gets a validation error ID for use in HTML which can be used as a CSS selector, or to reference an error message. This will return the error ID prefixed with `validate-error-`, unless the validation error has `hidden` set to true, or the validation error does not exist in the store.

#### _Parameters_ <!-- omit in toc -->

-   _errorId_ `string`: The error ID to get the validation error ID for.

#### _Returns_ <!-- omit in toc -->

-   `string`: The validation error ID for use in HTML.

#### _Example_ <!-- omit in toc -->

```js
const store = select( VALIDATION_STORE_KEY );
const billingFirstNameErrorId =
	store.getValidationErrorId( 'billing-first-name' );
```

### hasValidationErrors

Returns true if validation errors occurred, and false otherwise.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: Whether validation errors occurred.

#### _Example_ <!-- omit in toc -->

```js
const store = select( VALIDATION_STORE_KEY );
const hasValidationErrors = store.hasValidationErrors();
```

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/data-store/validation.md)

<!-- /FEEDBACK -->
