# useValidation

This custom hook uses the helper functions `const { lockPostSaving, unlockPostSaving } = useDispatch( 'core/editor' );` to lock/unlock the current editing product before saving it.

## Usage

Syncronous validation

```typescript
import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	__experimentalUseValidation as useValidation,
	ValidationError
} from '@woocommerce/product-editor';

const product = ...;

const validateTitle = useCallback( (): ValidationError => {
	if ( product.title.length < 2 ) {
		return __( 'Title must be more than 1 character', 'text-domain' );
	}
}, [ product.title ] );

const validationError = useValidation( 'product/title', validateTitle );
```

Asyncronous validation

```typescript
import { useCallback } from '@wordpress/element';
import {
	__experimentalUseValidation as useValidation,
	ValidationError
} from '@woocommerce/product-editor';

const product = ...;

const validateSlug = useCallback( async (): Promise< ValidationError > => {
	return fetch( `.../validate-slug?slug=${ product.slug }` )
		.then( ( response ) => response.json() )
		.then( ( { errorMessage } ) => errorMessage );
}, [ product.slug ] );

const validationError = useValidation( 'product/slug', validateSlug );
```
