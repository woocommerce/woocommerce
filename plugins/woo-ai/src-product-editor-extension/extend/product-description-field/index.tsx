/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import productSummaryFieldWithAi from './components/product-summary-field-with-ai';

addFilter(
	'blocks.registerBlockType',
	'woocommerce/product-name-field-with-ai',
	function ( settings, name ) {
		// Extend only the product summary field.
		if ( name !== 'woocommerce/product-summary-field' ) {
			return settings;
		}

		return {
			...settings,
			supports: {
				...settings.supports,
				__experimentalToolbar: true,
			},
			edit: productSummaryFieldWithAi( settings.edit ),
		};
	}
);
