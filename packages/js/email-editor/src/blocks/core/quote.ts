/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { Block } from '@wordpress/blocks/index';

/**
 * Remove the styles and alignment control support for the Quote block.
 */
function enhanceQuoteBlock() {
	addFilter(
		'blocks.registerBlockType',
		'woocommerce-email-editor/change-quote',
		( settings: Block, name ) => {
			if ( name === 'core/quote' ) {
				return {
					...settings,
					styles: [],
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

export { enhanceQuoteBlock };
