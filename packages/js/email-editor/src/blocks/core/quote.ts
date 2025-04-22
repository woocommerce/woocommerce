/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { Block } from '@wordpress/blocks/index';

/**
 * Remove the alignment control support for the Quote block.
 */
function disableQuoteAlignment() {
	addFilter(
		'blocks.registerBlockType',
		'woocommerce-email-editor/change-quote',
		( settings: Block, name ) => {
			if ( name === 'core/quote' ) {
				return {
					...settings,
					supports: {
						...settings.supports,
						align: [],
					},
				};
			}
			return settings;
		}
	);
}

export { disableQuoteAlignment };
