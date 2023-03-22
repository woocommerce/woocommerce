# useValidation

This custom hook uses the helper functions `const { lockPostSaving, unlockPostSaving } = useDispatch( 'core/editor' );` to lock/unlock the current editing product before saving it.

## Usage

Syncronous validation

```typescript
import { useCallback } from '@wordpress/element';
import { useValidation } from '@woocommerce/product-editor';

const product = ...;

const validateTitle = useCallback( (): boolean => {
	if ( product.title.length < 2 ) {
		return false;
	}
	return true;
}, [ product.title ] );

const isTitleValid = useValidation( 'product/title', validateTitle );
```

Asyncronous validation

```typescript
import { useCallback } from '@wordpress/element';
import { useValidation } from '@woocommerce/product-editor';

const product = ...;

const validateSlug = useCallback( async (): Promise< boolean > => {
	return fetch( `.../validate-slug?slug=${ product.slug }` )
		.then( ( response ) => response.json() )
		.then( ( { isValid } ) => isValid )
		.catch( () => false );
}, [ product.slug ] );

const isSlugValid = useValidation( 'product/slug', validateSlug );
```
