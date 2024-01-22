/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import productNameFieldWithAi from './components/product-name-field-with-ai';

addFilter(
	'blocks.registerBlockType',
	'woocommerce/product-name-field-with-ai',
	function ( settings, name ) {
		// Extend only the product name field block.
		if ( name !== 'woocommerce/product-name-field' ) {
			return settings;
		}

		return {
			...settings,
			supports: {
				...settings.supports,
				__experimentalToolbar: true,
			},
			edit: productNameFieldWithAi( settings.edit ),
		};
	}
);
