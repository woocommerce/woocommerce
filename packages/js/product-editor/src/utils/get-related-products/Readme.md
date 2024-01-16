# Get Related Products

Helper function to retrieve related products for a given product.

## Usage

`getRelatedProducts` is an asynchronous function that returns related products for a specified product ID.
If no related products are found, and the `fallbackToRandomProducts` flag is set to `true`, it returns a random set of products.

### Syntax

```es6
getRelatedProducts( productId, options )
```

#### Parameters

- `productId` (number): The ID of the product for which related products are to be fetched.
- `options` (Object): An object containing the following property:
+ `fallbackToRandomProducts` (boolean): Optional. If set to `true`, the function will return random products if no related products are found. Default is `false`.

#### Return

- Promise<Product[] | undefined>: A promise that resolves to an array of related products or `undefined` if none are found and `fallbackToRandomProducts` is `false`.

### Example

```javascript
import getRelatedProducts from './path-to-getRelatedProducts';

getRelatedProducts( 123, { fallbackToRandomProducts: true } )
  .then( relatedProducts => {
    console.log( relatedProducts );
  } )
  .catch( error => {
    console.error( 'Error fetching related products:', error );
  } );
```
