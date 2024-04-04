# Store Notices Store (`wc/store/store-notices`) <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Overview](#overview)
-   [Usage](#usage)
-   [Example](#example)
-   [Actions](#actions)
    -   [registerContainer( containerContext )](#registercontainer-containercontext-)
    -   [unregisterContainer( containerContext )](#unregistercontainer-containercontext-)
-   [Selectors](#selectors)
    -   [getRegisteredContainers](#getregisteredcontainers)

## Overview

The Store Notices Store allows to register and unregister containers for notices. This is useful for displaying notices in a specific location, such as a custom block.

## Usage

To utilize this store you will import the `STORE_NOTICES_STORE_KEY` in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the key via:

```js
const { STORE_NOTICES_STORE_KEY } = window.wc.wcBlocksData;
```

## Example

The following code snippet demonstrates how to register a container for notices.

```js
export default function Block( attributes ) {
	const context = 'your-namespace/custom-form-step';

	dispatch( 'core/notices' ).createNotice(
		'error',
		'This is an example of an error notice.',
		{ context }
	);

	return (
		<>
			<StoreNoticesContainer context={ context } />
			{ /* Your custom block code here */ }
		</>
	);
}
```

> 💡 Internally, the `StoreNoticesContainer` component will dispatch the `registerContainer` action.

Please note that this is a simple example. In practice, you will want to trigger the `createNotice` action in response to a user action, such as submitting a form.

## Actions

### registerContainer( containerContext )

This action will register a new container.

#### _Parameters_ <!-- omit in toc -->

-   _containerContext_ `string`: The context or identifier of the container to be registered.

#### _Returns_ <!-- omit in toc -->

-   `object`: An action object with the following properties:
    -   _type_ `string`: The type of the action.
    -   _containerContext_ `string`: The passed _containerContext_.

#### _Example_ <!-- omit in toc -->

```javascript
dispatch( registerContainer( 'someContainerContext' ) );
```

### unregisterContainer( containerContext )

This action will unregister an existing container.

#### _Parameters_ <!-- omit in toc -->

-   _containerContext_ `string`: The context or identifier of the container to be unregistered.

#### _Returns_ <!-- omit in toc -->

-   `object`: An action object with the following properties:
    -   _type_ `string`: The type of the action.
    -   _containerContext_ `string`: The passed _containerContext_.

#### _Example_ <!-- omit in toc -->

```js
dispatch( unregisterContainer( 'someContainerContext' ) );
```

## Selectors

### getRegisteredContainers

Returns the list of currently registered containers from the state.

#### _Returns_ <!-- omit in toc -->

-   `string[]`: An array of strings with the registered container contexts.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/store-notices' );
const registeredContainers = store.getRegisteredContainers();
```

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/data-store/validation.md)

<!-- /FEEDBACK -->
