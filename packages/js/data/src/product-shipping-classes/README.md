# Product Shipping Classes Data Store

This data store provides functions to interact with the [Product Shipping Class REST endpoints](https://woocommerce.github.io/woocommerce-rest-api-docs/#product-shipping-classes).
Under the hood this data store makes use of the [CRUD data store](../crud/README.md).

**Note: This data store is listed as experimental still as it is still in active development.**

## Usage

This data store can be accessed under the `experimental/wc/admin/products/shipping-classes` name. It is recommended you make use of the export constant `EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME`.

Example:

```js
import { EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';

function Component() {
	const actions = useDispatch(
		EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME
	);
	actions.createProductShippingClass( { name: 'test' } );
}
```

## Selections and actions:

| Selector                                       | Description                                                                                            |
| ---------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `getProductShippingClass( id: number )`        | Gets a Product Shipping Class by ID                                                                    |
| `getProductShippingClassError( id )`           | Get the error for a failing GET shipping class request.                                                |
| `getProductShippingClasses( query = {} )`      | Get all product shipping classes, optionally by a specific query, see `Query` type [here](./types.ts). |
| `getProductShippingClassesError( query = {} )` | Get the error for a GET request for all shipping classes.                                              |

Example usage: `wp.data.select( EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME ).getProductShippingClass( 3 );`

| Actions                                                  | Method | Description                                                                        |
| -------------------------------------------------------- | ------ | ---------------------------------------------------------------------------------- |
| `createProductShippingClass( shippingClassObject )`      | POST   | Creates shipping class, see `ProductShippingClass` [here](./types.ts) for values   |
| `deleteProductShippingClass( id )`                       | DELETE | Deletes a shipping class by ID                                                     |
| `updatetProductShippingClass( id, shippingClassObject )` | PUT    | Updates a shipping class, see `ProductShippingClass` [here](./types.ts) for values |

Example usage: `wp.data.dispatch( EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME ).updateProductShippingClass( 3, { name: 'New name' } );`
