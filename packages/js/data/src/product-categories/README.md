# Product Categories Data Store

This data store provides functions to interact with the [Product Category REST endpoints](https://woocommerce.github.io/woocommerce-rest-api-docs/#product-categories).
Under the hood this data store makes use of the [CRUD data store](../crud/README.md).

**Note: This data store is listed as experimental still as it is still in active development.**

## Usage

This data store can be accessed under the `experimental/wc/admin/products/categories` name. It is recommended you make use of the export constant `EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME`.

Example:

```js
import { EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';

function Component() {
	const actions = useDispatch(
		EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME
	);
	actions.createProductCategory( { name: 'test' } );
}
```

## Selections and actions:

| Selector                                       | Description                                                                                            |
| ---------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `getProductCategory( id: number )`        | Gets a Product Category by ID                                                                    |
| `getProductCategoryError( id )`           | Get the error for a failing GET product category request.                                        |
| `getProductCategories( query = {} )`      | Get all product categories, optionally by a specific query, see `Query` type [here](./types.ts). |
| `getProductCategoriesError( query = {} )` | Get the error for a GET request for all categories.                                              |

Example usage: `wp.data.select( EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME ).getProductCategory( 3 );`

| Actions                                                  | Method | Description                                                                        |
| -------------------------------------------------------- | ------ | ---------------------------------------------------------------------------------- |
| `createProductCategory( shippingClassObject )`      | POST   | Create product category, see `ProductCategory` [here](./types.ts) for values   |
| `deleteProductCategory( id )`                       | DELETE | Delete a product category by ID                                                |
| `updatetProductCategory( id, shippingClassObject )` | PUT    | Update a product category, see `ProductCategory` [here](./types.ts) for values |

Example usage: `wp.data.dispatch( EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME ).updateProductCategory( 3, { name: 'New name' } );`
