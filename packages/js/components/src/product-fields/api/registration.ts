/**
 * External dependencies
 */
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store as productFieldStore } from '../store';
import { ProductFieldDefinition } from '../store/types';

/**
 * Registers a new product field provided a unique name and an object defining its
 * behavior. Once registered, the field is made available to use with the product form API.
 *
 * @param {string|Object} fieldName Field name.
 * @param {Object}        settings  Field settings.
 *
 * @example
 * ```js
 * import { registerProductField } from '@woocommerce/components'
 *
 * registerProductFieldType( 'attributes-field', {
 * } );
 * ```
 */
export function registerProductField(
	fieldName: string,
	settings: ProductFieldDefinition
) {
	const name = fieldName;

	if ( select( productFieldStore ).getProductField( name ) ) {
		// eslint-disable-next-line no-console
		console.error( 'Product Field "' + name + '" is already registered.' );
		return;
	}

	const blockType = {
		attributes: {},
		...settings,
	};

	dispatch( productFieldStore ).registerProductField( blockType );

	return select( productFieldStore ).getProductField( name );
}
