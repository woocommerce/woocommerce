---
sidebar_label: Store Notices Store
---
# Store Notices Store (`wc/store/store-notices`) 

## Overview

The Store Notices Store allows to register and unregister containers for notices. This is useful for displaying notices in a specific location, such as a custom block.

## Usage

To utilize this store you will import the `storeNoticesStore` `StoreDescriptor` in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the `StoreDescriptor` via:

```js
import { storeNoticesStore } from '@woocommerce/block-data';
```

If it's not, then access it from the window like so:

```js
const { storeNoticesStore } = window.wc.wcBlocksData;
```

## Example

The following code snippet demonstrates how to register a container for notices.

```js
import { store as noticesStore } from '@wordpress/notices';

export default function Block( attributes ) {
	const context = 'your-namespace/custom-form-step';

	dispatch( noticesStore ).createNotice(
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

#### _Parameters_ 

-   _containerContext_ `string`: The context or identifier of the container to be registered.

#### _Returns_ 

-   `object`: An action object with the following properties:
    -   _type_ `string`: The type of the action.
    -   _containerContext_ `string`: The passed _containerContext_.

#### _Example_ 

```javascript
import { storeNoticesStore } from '@woocommerce/block-data';

dispatch( storeNoticesStore ).registerContainer( 'someContainerContext' );
```

### unregisterContainer( containerContext )

This action will unregister an existing container.

#### _Parameters_ 

-   _containerContext_ `string`: The context or identifier of the container to be unregistered.

#### _Returns_ 

-   `object`: An action object with the following properties:
    -   _type_ `string`: The type of the action.
    -   _containerContext_ `string`: The passed _containerContext_.

#### _Example_ 

```js
import { storeNoticesStore } from '@woocommerce/block-data';

dispatch( storeNoticesStore ).unregisterContainer( 'someContainerContext' );
```

## Selectors

### getRegisteredContainers

Returns the list of currently registered containers from the state.

#### _Returns_ 

-   `string[]`: An array of strings with the registered container contexts.

#### _Example_ 

```js
import { storeNoticesStore } from '@woocommerce/block-data';

const store = select( storeNoticesStore );
const registeredContainers = store.getRegisteredContainers();
```
