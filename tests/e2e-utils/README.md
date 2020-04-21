# WooCommerce End to End Test Utilities 

This package contains utilities needed to write e2e tests (specific to WooCommerce).


## Installation

```bash
npm install @woocommerce/e2e-utils --save
```

## Usage

Example:
~~~js
import {
	CustomerFlow,
	StoreOwnerFlow,
	createSimpleProduct,
	uiUnblocked
} from '@woocommerce/e2e-utils';

describe( 'Cart page', () => {
	beforeAll( async () => {
		await StoreOwnerFlow.login();
		await createSimpleProduct();
		await StoreOwnerFlow.logout();
	} );

	it( 'should display no item in the cart', async () => {
		await CustomerFlow.goToCart();
		await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
	} );
} );
~~~