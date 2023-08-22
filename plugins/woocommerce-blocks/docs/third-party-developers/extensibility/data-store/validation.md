# wc/store/validation

## Table of Contents

-   [Overview](#overview)
-   [Selectors](#selectors)
    -   [getValidationError](#getvalidationerror)
    -   [getValidationErrorId](#getvalidationerrorid)
    -   [hasValidationErrors](#hasvalidationerrors)
-   [Actions](#actions)
    -   [clearValidationError](#clearvalidationerror)
    -   [clearValidationErrors](#clearvalidationerrors)
    -   [setValidationErrors](#setvalidationerrors)
    -   [hideValidationError](#hidevalidationerror)
    -   [showValidationError](#showvalidationerror)
    -   [showAllValidationErrors](#showallvalidationerrors)

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

## Selectors

### getValidationError

Returns the validation error.

#### _Parameters_

-   _errorId_ `string` - The error ID to get validation errors for.

#### Example

```js
const store = select( 'wc/store/validation' );
const billingFirstNameError = store.getValidationError( 'billing-first-name' );
```

#### _Returns_

-   `object`: The validation error which is an object containing _message_ (`string`) and _hidden_ (`boolean`).

### getValidationErrorId

Gets a validation error ID for use in HTML which can be used as a CSS selector, or to reference an error message. This will return the error ID prefixed with `validate-error-`, unless the validation error has `hidden` set to true, or the validation error does not exist in the store.

#### _Parameters_

-   _errorId_ `string` - The error ID to get the validation error ID for.

#### _Returns_

-   `string`: The validation error ID for use in HTML.

### hasValidationErrors

Returns true if validation errors occurred, and false otherwise.

#### _Returns_

-   `boolean`: Whether validation errors occurred.

## Actions

### clearValidationError

Clears a validation error.

#### _Parameters_

-   _errorId_ `string` - The error ID to clear validation errors for.

#### Example

```js
const store = dispatch( 'wc/store/validation' );
store.clearValidationError( 'billing-first-name' );
```

### clearValidationErrors

Clears multiple validation errors at once. If no error IDs are passed, all validation errors will be cleared.

#### _Parameters_

-   _errors_ `string[] | undefined` - The error IDs to clear validation errors for. This can be undefined, and if it is, all validation errors will be cleared.

#### Example

1. This will clear only the validation errors passed in the array.

```js
const store = dispatch( 'wc/store/validation' );
store.clearValidationErrors( [
	'billing-first-name',
	'billing-last-name',
	'terms-and-conditions',
] );
```

2. This will clear all validation errors.

```js
const store = dispatch( 'wc/store/validation' );
store.clearValidationErrors();
```

### setValidationErrors

#### _Parameters_

-   _errors_ `object`: An object containing new validation errors, the keys of the object are the validation error IDs, and the values should be objects containing _message_ (`string`) and _hidden_ `boolean`.

Sets the validation errors. The entries in _errors_ will be _added_ to the list of validation errors. Any entries that already exist in the list will be _updated_ with the new values.

#### Example

```js
const { dispatch } = wp.data;
const { setValidationErrors } = dispatch( 'wc/store/validation' );

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

### hideValidationError

Hides a validation error by setting the `hidden` property to `true`. This will _not_ clear it from the data store!

#### _Parameters_

-   _errorId_ `string`: The error ID to hide.

#### Example

```js
const { dispatch } = wp.data;
const { hideValidationError } = dispatch( 'wc/store/validation' );

hideValidationError( 'billing-first-name' );
```

### showValidationError

Shows a validation error by setting the `hidden` property to `false`.

#### _Parameters_

-   _errorId_ `string`: The error ID to show.

#### Example

```js
const { dispatch } = wp.data;
const { showValidationError } = dispatch( 'wc/store/validation' );

showValidationError( 'billing-first-name' );
```

### showAllValidationErrors

Shows all validation errors by setting the `hidden` property to `false`.

#### Example

```js
const { dispatch } = wp.data;
const { showAllValidationErrors } = dispatch( 'wc/store/validation' );

showAllValidationErrors();
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/data-store/validation.md)

<!-- /FEEDBACK -->

